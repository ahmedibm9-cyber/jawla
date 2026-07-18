import http from 'k6/http';
import { check, sleep, group } from 'k6';
import { Rate, Trend } from 'k6/metrics';

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';
const httpErrorRate = new Rate('http_errors');
const pageLoadMs = new Trend('page_load_ms');

const ADMIN = { email: 'admin@jawla.test', password: 'password' };

function unescapeHtml(str) {
  return str
    .replace(/&amp;/g, '&')
    .replace(/&lt;/g, '<')
    .replace(/&gt;/g, '>')
    .replace(/&quot;/g, '"')
    .replace(/&#039;/g, "'");
}

function csrf(html) {
  const m = html.match(/<meta name="csrf-token" content="([^"]+)"/);
  return m ? m[1] : '';
}

function extractSnapshot(html) {
  const m = html.match(/wire:snapshot="([^"]+)"/);
  if (!m) return null;
  return unescapeHtml(m[1]);
}

function checkHealth() {
  group('health', () => {
    let r = http.get(`${BASE_URL}/up`);
    check(r, { '/up 200': (x) => x.status === 200 });
    httpErrorRate.add(r.status !== 200);
    r = http.get(`${BASE_URL}/health`);
    check(r, { '/health ok': (x) => x.status === 200 && x.json('status') === 'ok' });
    httpErrorRate.add(r.status !== 200);
  });
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
        updates: {
          'data.email': ADMIN.email,
          'data.password': ADMIN.password,
          'data.remember': true,
        },
        calls: [{
          method: 'authenticate',
          params: [],
          path: '',
        }],
      }],
    });

    r = http.post(`${BASE_URL}/livewire/update`, payload, {
      headers: {
        'Content-Type': 'application/json',
        'X-Livewire': 'true',
        'Accept': 'application/json',
      },
    });

    check(r, { 'login POST 200': (x) => x.status === 200 });
    httpErrorRate.add(r.status >= 500);

    if (r.status === 200) {
      try {
        const body = JSON.parse(r.body);
        const redirectUrl = body?.components?.[0]?.effects?.redirect;
        check(r, { 'login redirect received': !!redirectUrl });
        if (redirectUrl) {
          http.get(redirectUrl.startsWith('http') ? redirectUrl : `${BASE_URL}${redirectUrl}`);
        }
      } catch (e) {
        console.log('Parse error:', e);
      }
    }

    sleep(0.5);
  });
}

function adminBrowse() {
  group('admin-dashboard', () => {
    let r = http.get(`${BASE_URL}/admin`);
    check(r, { 'dashboard 200': (x) => x.status === 200 });
    pageLoadMs.add(r.timings.duration);
    httpErrorRate.add(r.status >= 500);
    sleep(1);
  });

  const pages = ['/admin/users', '/admin/customers', '/admin/products', '/admin/invoices',
                 '/admin/reports-page', '/admin/stocks', '/admin/daily-visit-assignments',
                 '/admin/companies', '/admin/routes', '/admin/purchase-requests',
                 '/admin/alarms', '/admin/complaints'];

  for (const p of pages) {
    group(`admin-${p.replace('/admin/', '')}`, () => {
      let r = http.get(`${BASE_URL}${p}`);
      check(r, { [`${p}`]: (x) => x.status === 200 || x.status === 302 });
      pageLoadMs.add(r.timings.duration);
      httpErrorRate.add(r.status >= 500);
      sleep(0.2);
    });
  }
}

export default function () {
  checkHealth();
  adminSession();
  adminBrowse();
}

export const options = {
  scenarios: {
    smoke: {
      executor: 'constant-vus',
      vus: 1,
      duration: '30s',
      tags: { test_type: 'smoke' },
      gracefulStop: '10s',
    },
    load: {
      executor: 'ramping-vus',
      startVUs: 0,
      stages: [
        { duration: '1m', target: 5 },
        { duration: '3m', target: 5 },
        { duration: '1m', target: 0 },
      ],
      startTime: '40s',
      tags: { test_type: 'load' },
      gracefulStop: '30s',
    },
    stress: {
      executor: 'ramping-vus',
      startVUs: 0,
      stages: [
        { duration: '1m', target: 5 },
        { duration: '2m', target: 5 },
        { duration: '1m', target: 15 },
        { duration: '2m', target: 15 },
        { duration: '1m', target: 30 },
        { duration: '2m', target: 30 },
        { duration: '1m', target: 0 },
      ],
      startTime: '6m',
      tags: { test_type: 'stress' },
      gracefulStop: '30s',
    },
    spike: {
      executor: 'ramping-vus',
      startVUs: 0,
      stages: [
        { duration: '10s', target: 50 },
        { duration: '1m', target: 50 },
        { duration: '30s', target: 0 },
      ],
      startTime: '16m',
      tags: { test_type: 'spike' },
      gracefulStop: '30s',
    },
    soak: {
      executor: 'ramping-vus',
      startVUs: 0,
      stages: [
        { duration: '2m', target: 10 },
        { duration: '10m', target: 10 },
        { duration: '1m', target: 0 },
      ],
      startTime: '18m',
      tags: { test_type: 'soak' },
      gracefulStop: '30s',
    },
  },
  thresholds: {
    http_req_duration: ['p(95)<3000', 'p(99)<8000'],
    http_req_failed: ['rate<0.05'],
    page_load_ms: ['p(95)<4000'],
    http_errors: ['rate<0.10'],
  },
};
