'use client';

import FinancialMetrics from '@/components/FinancialMetrics';
import QuickActions from '@/components/QuickActions';
import ActivityFeed from '@/components/ActivityFeed';

export default function Dashboard() {
  return (
    <div className="p-8">
      <div className="mb-8">
        <h1 className="text-4xl font-bold text-white mb-2">لوحة التحكم</h1>
        <p className="text-slate-400">مرحبا بك في نظام إدارة العملاء المتقدم</p>
      </div>

      {/* Financial Metrics */}
      <FinancialMetrics />

      {/* Quick Actions */}
      <QuickActions />

      {/* Activity Feed and Summary */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div className="lg:col-span-2">
          <ActivityFeed />
        </div>

        {/* Key Metrics Summary */}
        <div className="bg-slate-800 border border-slate-700 rounded-lg p-6">
          <h2 className="text-white text-lg font-bold mb-6">ملخص رئيسي</h2>

          <div className="space-y-4">
            <div className="p-4 bg-slate-700/50 rounded-lg">
              <p className="text-slate-400 text-sm mb-2">عملاء نشطون</p>
              <p className="text-white text-2xl font-bold">487</p>
              <p className="text-green-400 text-xs mt-2">↑ 12.5% هذا الشهر</p>
            </div>

            <div className="p-4 bg-slate-700/50 rounded-lg">
              <p className="text-slate-400 text-sm mb-2">معدل التحويل</p>
              <p className="text-white text-2xl font-bold">38.4%</p>
              <p className="text-green-400 text-xs mt-2">↑ 5.2% هذا الشهر</p>
            </div>

            <div className="p-4 bg-slate-700/50 rounded-lg">
              <p className="text-slate-400 text-sm mb-2">متوسط قيمة الطلب</p>
              <p className="text-white text-2xl font-bold">28.5K</p>
              <p className="text-yellow-400 text-xs mt-2">→ مستقرة</p>
            </div>

            <div className="p-4 bg-slate-700/50 rounded-lg">
              <p className="text-slate-400 text-sm mb-2">رضا العملاء</p>
              <div className="flex items-center gap-2 mt-2">
                <div className="text-white text-2xl font-bold">4.7</div>
                <div className="text-yellow-400">★★★★★</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
