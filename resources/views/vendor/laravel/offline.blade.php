<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="icon" href="/images/logo-app-icon.webp" type="image/png">
<title>Jawla — Offline</title>
<style>
body{font-family:'IBM Plex Sans Arabic',system-ui,sans-serif;margin:0;display:flex;align-items:center;justify-content:center;min-height:100dvh;background:#F8FAFC;text-align:center;padding:24px}
.card{background:#fff;border-radius:16px;padding:32px;max-width:480px;width:100%;box-shadow:0 2px 8px rgba(0,0,0,.08)}
.card img{height:56px;width:auto;margin-bottom:16px}
h1{font-size:1.25rem;margin:0 0 8px;color:#0F172A}
p{color:#475569;margin:0 0 16px;font-size:.875rem;line-height:1.5}
.btn{display:inline-block;padding:10px 24px;background:#3D7A18;color:#fff;border-radius:8px;text-decoration:none;font-size:.875rem}
.cached{margin-top:24px;text-align:left}
.cached h2{font-size:1rem;margin:0 0 12px;color:#0F172A;border-bottom:1px solid #E2E8F0;padding-bottom:8px}
.cached .section{margin-bottom:16px}
.cached .section h3{font-size:.8rem;color:#64748B;text-transform:uppercase;letter-spacing:.05em;margin:0 0 6px}
.cached .item{padding:8px 12px;background:#F1F5F9;border-radius:8px;margin-bottom:4px;font-size:.8125rem;color:#334155;display:flex;justify-content:space-between}
.cached .item .name{font-weight:500}
.cached .item .detail{color:#64748B}
.cached .empty{color:#94A3B8;font-size:.8125rem;font-style:italic}
.cached .ts{color:#94A3B8;font-size:.75rem;margin-top:12px}
</style>
</head>
<body>
<main class="card">
    <img src="/images/green-j.webp" alt="Jawla">
    <h1>{{ l('لا يوجد اتصال بالإنترنت', 'No Internet Connection') }}</h1>
    <p>{{ l('يرجى التحقق من اتصالك بالإنترنت والمحاولة مرة أخرى', 'Please check your connection and try again') }}</p>
    <a class="btn" href="/app">{{ l('إعادة المحاولة', 'Retry') }}</a>
    <div id="cached-data" class="cached" style="display:none"></div>
</main>
<script>
// Show cached offline data if available
(function() {
  const DB_NAME = "jawla-cache";
  const DB_VERSION = 1;
  const el = document.getElementById("cached-data");
  const isAr = document.documentElement.lang === "ar";

  function openDb() {
    return new Promise(function(resolve, reject) {
      var req = indexedDB.open(DB_NAME, DB_VERSION);
      req.onupgradeneeded = function() {
        var db = req.result;
        if (!db.objectStoreNames.contains("datasets")) db.createObjectStore("datasets", { keyPath: "key" });
        if (!db.objectStoreNames.contains("meta")) db.createObjectStore("meta", { keyPath: "key" });
      };
      req.onsuccess = function() { resolve(req.result); };
      req.onerror = function() { reject(req.error); };
    });
  }

  function getDataset(db, name) {
    return new Promise(function(resolve) {
      var tx = db.transaction("datasets", "readonly");
      var req = tx.objectStore("datasets").get(name);
      req.onsuccess = function() { resolve(req.result?.data || null); };
      req.onerror = function() { resolve(null); };
    });
  }

  function getMeta(db) {
    return new Promise(function(resolve) {
      var tx = db.transaction("meta", "readonly");
      var req = tx.objectStore("meta").get("offline-snapshot");
      req.onsuccess = function() { resolve(req.result || null); };
      req.onerror = function() { resolve(null); };
    });
  }

  async function render() {
    try {
      var db = await openDb();
      var meta = await getMeta(db);
      if (!meta) return;

      var customers = await getDataset(db, "customers");
      var products = await getDataset(db, "products");
      var stock = await getDataset(db, "stock");
      var assignments = await getDataset(db, "assignments");

      var html = "";
      var hasData = false;

      if (assignments && assignments.length) {
        hasData = true;
        html += '<div class="section"><h3>' + (isAr ? "زيارات اليوم" : "Today's Visits") + '</h3>';
        assignments.forEach(function(a) {
          var name = a.customer?.name_ar || a.customer?.name_en || "—";
          html += '<div class="item"><span class="name">' + name + '</span><span class="detail">#' + (a.sort_order || "") + '</span></div>';
        });
        html += '</div>';
      }

      if (customers && customers.length) {
        hasData = true;
        html += '<div class="section"><h3>' + (isAr ? "العملاء" : "Customers") + ' (' + customers.length + ')</h3>';
        customers.slice(0, 20).forEach(function(c) {
          var name = isAr ? (c.name_ar || c.name_en) : (c.name_en || c.name_ar);
          html += '<div class="item"><span class="name">' + name + '</span><span class="detail">' + (c.phone || "") + '</span></div>';
        });
        if (customers.length > 20) html += '<div class="item"><span class="detail">… +' + (customers.length - 20) + '</span></div>';
        html += '</div>';
      }

      if (products && products.length) {
        hasData = true;
        html += '<div class="section"><h3>' + (isAr ? "المنتجات" : "Products") + ' (' + products.length + ')</h3>';
        products.slice(0, 15).forEach(function(p) {
          var name = isAr ? (p.name_ar || p.name_en) : (p.name_en || p.name_ar);
          html += '<div class="item"><span class="name">' + name + '</span><span class="detail">' + (p.sku || "") + '</span></div>';
        });
        if (products.length > 15) html += '<div class="item"><span class="detail">… +' + (products.length - 15) + '</span></div>';
        html += '</div>';
      }

      if (stock && stock.length) {
        hasData = true;
        html += '<div class="section"><h3>' + (isAr ? "المخزون" : "Van Stock") + ' (' + stock.length + ')</h3>';
        stock.slice(0, 10).forEach(function(s) {
          html += '<div class="item"><span class="name">#' + s.product_id + '</span><span class="detail">' + s.quantity + '</span></div>';
        });
        if (stock.length > 10) html += '<div class="item"><span class="detail">… +' + (stock.length - 10) + '</span></div>';
        html += '</div>';
      }

      if (hasData) {
        html += '<div class="ts">' + (isAr ? "آخر تحديث:" : "Last updated:") + ' ' + new Date(meta.cachedAt).toLocaleString() + '</div>';
        el.innerHTML = html;
        el.style.display = "block";
      }
    } catch(e) {
      // IndexedDB not available or error — silently ignore
    }
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", render);
  } else {
    render();
  }
})();
</script>
</body>
</html>
