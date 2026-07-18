import http from 'k6/http';
import { check, sleep, group } from 'k6';
import { Rate, Trend } from 'k6/metrics';

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';
const httpErrorRate = new Rate('http_errors');
const pageLoadMs = new Trend('page_load_ms');

const ADMIN = { email: 'admin@jawla.test', password: 'password' };

function csrf(html) {
  const m = html.match(/<meta name="csrf-token" content="([^"]+)"/);
  return m ? m[1] : '';
}

function extractSnapshot(html) {
  const m = html.match(/wire:snapshot="([^"]+)"/);
  if (!m) return null;
  return m[1].replace(/&amp;/g, '&').replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&quot;/g, '"').replace(/&#039;/g, "'");
}

function adminSession() {
  group('admin-login', () => {
    let r = http.get(`${BASE_URL}/admin/login`);
    check(r, { 'login page 200': (x) => x.status === 200 });
    pageLoadMs.add(r.timings.duration);
    httpErrorRate.add(r.status !== 200);

    const token = csrf(r.body);
    const snapshot = extractSnapshot(r.body);
    check(r, { 'snapshot extracted': (x) => snapshot !== null });
    if (!snapshot) return;

    const payload = JSON.stringify({
      _token: token,
      components: [{
        snapshot: snapshot,
        updates: { 'data.email': ADMIN.email, 'data.password': ADMIN.password, 'data.remember': true },
        calls: [{ method: 'authenticate', params: [], path: '' }],
      }],
    });

    r = http.post(`${BASE_URL}/livewire/update`, payload, {
      headers: { 'Content-Type': 'application/json', 'X-Livewire': 'true', 'Accept': 'application/json' },
    });

    check(r, { 'login POST 200': (x) => x.status === 200 });
    httpErrorRate.add(r.status >= 500);

    if (r.status === 200) {
      try {
        const body = JSON.parse(r.body);
        const redirectUrl = body?.components?.[0]?.effects?.redirect;
        if (redirectUrl) {
          http.get(redirectUrl.startsWith('http') ? redirectUrl : `${BASE_URL}${redirectUrl}`);
        }
      } catch (e) {}
    }
    sleep(0.3);
  });
}

function browse() {
  group('admin-dashboard', () => {
    let r = http.get(`${BASE_URL}/admin`);
    check(r, { 'dashboard 200': (x) => x.status === 200 });
    pageLoadMs.add(r.timings.duration);
    httpErrorRate.add(r.status >= 500);
    sleep(0.3);
  });
  const pages = ['/admin/customers', '/admin/products', '/admin/invoices'];
  for (const p of pages) {
    let r = http.get(`${BASE_URL}${p}`);
    check(r, { [`${p}`]: (x) => x.status === 200 || x.status === 302 });
    pageLoadMs.add(r.timings.duration);
    httpErrorRate.add(r.status >= 500);
    sleep(0.1);
  }
}

export default function () {
  adminSession();
  browse();
}

export const options = {
  scenarios: {
    load_test: {
      executor: 'ramping-vus',
      startVUs: 0,
      stages: [
        { duration: '30s', target: 5 },
        { duration: '30s', target: 5 },
        { duration: '30s', target: 15 },
        { duration: '30s', target: 15 },
        { duration: '30s', target: 30 },
        { duration: '30s', target: 30 },
        { duration: '30s', target: 0 },
      ],
      gracefulStop: '10s',
    },
  },
  thresholds: {
    http_req_duration: ['p(95)<5000', 'p(99)<10000'],
    http_req_failed: ['rate<0.10'],
  },
};
