export function logoutMessage(locale, reason) {
  const arabic = locale === "ar";

  if (reason === "pending") {
    return arabic
      ? "لا تزال هناك مبيعات أو مدفوعات أو عمليات غير متزامنة على هذا الجهاز. احتفظت جولة بها بأمان. قم بمزامنتها أو معالجتها قبل تسجيل الخروج."
      : "This device still has unsynced sales, payments, or other operations. Jawla kept them safe. Sync or resolve them before logging out.";
  }

  return arabic
    ? "تعذر على جولة التحقق من البيانات غير المتزامنة بأمان، لذلك تم إيقاف تسجيل الخروج. حاول مرة أخرى أو تواصل مع الدعم."
    : "Jawla could not safely verify the offline data, so logout was stopped. Try again or contact support.";
}

export async function prepareSafeLogout({ sync, offline }) {
  try {
    // The queue is the source of truth for locally persisted financial work.
    // If its status cannot be read, retain the cache and stop the logout.
    if (typeof sync?.hasPending !== "function") {
      return { allowed: false, reason: "verification-failed" };
    }

    if (await sync.hasPending()) {
      return { allowed: false, reason: "pending" };
    }

    await offline?.clear?.();

    return { allowed: true, reason: null };
  } catch {
    return { allowed: false, reason: "verification-failed" };
  }
}
