import "./pwa-register.js";
import L from "leaflet";

const PRINTER_STORAGE_KEY = "jawla.bluetooth-printer";
const PRINTER_SERVICE_UUIDS = [
  0xffe0,
  "49535343-fe7d-4ae5-8fa9-9fafd205e455",
  "0000ae30-0000-1000-8000-00805f9b34fb",
];
const KNOWN_CHARACTERISTIC_UUIDS = [
  "0000ffe1-0000-1000-8000-00805f9b34fb",
  "49535343-8841-43f4-a8d4-ecbe34729bb3",
  "0000ae01-0000-1000-8000-00805f9b34fb",
];

function sleep(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

function chunkBytes(bytes, size = 180) {
  const chunks = [];
  for (let i = 0; i < bytes.length; i += size) {
    chunks.push(bytes.slice(i, i + size));
  }
  return chunks;
}

function utf8Bytes(text) {
  return Array.from(new TextEncoder().encode(text));
}

function qrBytes(text) {
  const data = utf8Bytes(text);
  const storeLength = data.length + 3;
  const pL = storeLength % 256;
  const pH = Math.floor(storeLength / 256);

  return [
    0x1d,
    0x28,
    0x6b,
    0x04,
    0x00,
    0x31,
    0x41,
    0x32,
    0x00,
    0x1d,
    0x28,
    0x6b,
    0x03,
    0x00,
    0x31,
    0x43,
    0x06,
    0x1d,
    0x28,
    0x6b,
    0x03,
    0x00,
    0x31,
    0x45,
    0x30,
    0x1b,
    0x61,
    0x01,
    0x1d,
    0x28,
    0x6b,
    pL,
    pH,
    0x31,
    0x50,
    0x30,
    ...data,
    0x1d,
    0x28,
    0x6b,
    0x03,
    0x00,
    0x31,
    0x51,
    0x30,
    0x1b,
    0x61,
    0x00,
  ];
}

function payloadToEscPos(payload) {
  const bytes = [0x1b, 0x40];

  for (const line of payload.lines || []) {
    bytes.push(...utf8Bytes(`${line}\n`));
  }

  if (payload.qr) {
    bytes.push(...utf8Bytes("\n"));
    bytes.push(...qrBytes(payload.qr));
    bytes.push(...utf8Bytes("\n"));
  }

  bytes.push(0x1d, 0x56, 0x42, 0x00);
  bytes.push(...utf8Bytes("\n\n\n"));

  return new Uint8Array(bytes);
}

async function findWritableCharacteristic(server) {
  const preferredServices = [];

  for (const uuid of PRINTER_SERVICE_UUIDS) {
    try {
      preferredServices.push(await server.getPrimaryService(uuid));
    } catch {
      // ignore missing known service
    }
  }

  const allServices =
    preferredServices.length > 0
      ? preferredServices
      : await server.getPrimaryServices();

  for (const service of allServices) {
    for (const knownUuid of KNOWN_CHARACTERISTIC_UUIDS) {
      try {
        const characteristic = await service.getCharacteristic(knownUuid);
        if (
          characteristic.properties.write ||
          characteristic.properties.writeWithoutResponse
        ) {
          return characteristic;
        }
      } catch {
        // keep searching
      }
    }

    const characteristics = await service.getCharacteristics();
    for (const characteristic of characteristics) {
      if (
        characteristic.properties.write ||
        characteristic.properties.writeWithoutResponse
      ) {
        return characteristic;
      }
    }
  }

  throw new Error("No writable printer characteristic found.");
}

async function getSavedDevice() {
  const saved = window.localStorage.getItem(PRINTER_STORAGE_KEY);
  if (!saved || !navigator.bluetooth?.getDevices) {
    return null;
  }

  const { id } = JSON.parse(saved);
  const devices = await navigator.bluetooth.getDevices();

  return devices.find((device) => device.id === id) || null;
}

window.jawlaBluetoothPrinter = ({ payload }) => ({
  payload,
  busy: false,
  supported: Boolean(navigator.bluetooth),
  hasSavedPrinter: Boolean(window.localStorage.getItem(PRINTER_STORAGE_KEY)),
  message: navigator.bluetooth
    ? document.documentElement.lang === "ar"
      ? "اطبع مباشرة أو قم بإقران الطابعة أولاً."
      : "Print now or pair a printer first."
    : document.documentElement.lang === "ar"
      ? "هذا المتصفح لا يدعم Bluetooth للطباعة. استخدم ملف PDF."
      : "This browser does not support Bluetooth printing. Use the PDF instead.",

  async pairPrinter() {
    if (!this.supported) {
      return;
    }

    this.busy = true;
    this.message =
      document.documentElement.lang === "ar"
        ? "جاري إقران الطابعة…"
        : "Pairing printer…";

    try {
      const device = await navigator.bluetooth.requestDevice({
        acceptAllDevices: true,
        optionalServices: PRINTER_SERVICE_UUIDS,
      });

      window.localStorage.setItem(
        PRINTER_STORAGE_KEY,
        JSON.stringify({
          id: device.id,
          name: device.name || "Bluetooth printer",
        })
      );
      this.hasSavedPrinter = true;
      this.message =
        document.documentElement.lang === "ar"
          ? `تم حفظ الطابعة: ${device.name || "Bluetooth printer"}`
          : `Saved printer: ${device.name || "Bluetooth printer"}`;
    } catch (error) {
      this.message =
        error?.message ||
        (document.documentElement.lang === "ar"
          ? "تعذر إقران الطابعة."
          : "Could not pair the printer.");
    } finally {
      this.busy = false;
    }
  },

  forgetPrinter() {
    window.localStorage.removeItem(PRINTER_STORAGE_KEY);
    this.hasSavedPrinter = false;
    this.message =
      document.documentElement.lang === "ar"
        ? "تم مسح الطابعة المحفوظة."
        : "Saved printer cleared.";
  },

  async printDocument() {
    if (!this.supported) {
      return;
    }

    this.busy = true;
    this.message =
      document.documentElement.lang === "ar"
        ? "جاري تجهيز الطباعة…"
        : "Preparing print…";

    try {
      let device = await getSavedDevice();
      if (!device) {
        await this.pairPrinter();
        device = await getSavedDevice();
      }

      if (!device) {
        throw new Error(
          document.documentElement.lang === "ar"
            ? "اختر طابعة أولاً."
            : "Choose a printer first."
        );
      }

      const server = device.gatt?.connected
        ? device.gatt
        : await device.gatt.connect();
      const characteristic = await findWritableCharacteristic(server);
      const bytes = payloadToEscPos(this.payload);

      for (const chunk of chunkBytes(bytes)) {
        if (typeof characteristic.writeValueWithoutResponse === "function") {
          // eslint-disable-next-line no-await-in-loop
          await characteristic.writeValueWithoutResponse(chunk);
        } else {
          // eslint-disable-next-line no-await-in-loop
          await characteristic.writeValue(chunk);
        }
        // eslint-disable-next-line no-await-in-loop
        await sleep(30);
      }

      this.message =
        document.documentElement.lang === "ar"
          ? "تم إرسال الطباعة إلى الطابعة."
          : "Print sent to the printer.";
    } catch (error) {
      this.message =
        error?.message ||
        (document.documentElement.lang === "ar"
          ? "فشلت الطباعة."
          : "Printing failed.");
    } finally {
      this.busy = false;
    }
  },
});

window.L = L;
