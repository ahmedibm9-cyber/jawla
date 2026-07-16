export default function Dashboard() {
  const widgets = [
    { title: 'الزيارات اليوم', value: '24', icon: '📍', color: 'blue' },
    { title: 'المبيعات اليوم', value: '45,320 ر.س', icon: '💰', color: 'green' },
    { title: 'العروض المعلقة', value: '8', icon: '📄', color: 'yellow' },
    { title: 'التنبيهات المفتوحة', value: '3', icon: '⚠️', color: 'red' },
  ];

  return (
    <div className="p-8">
      <h2 className="text-3xl font-bold text-white mb-8">لوحة التحكم</h2>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        {widgets.map((widget, idx) => (
          <div
            key={idx}
            className="bg-slate-800 border border-slate-700 rounded-lg p-6 hover:border-slate-600 transition"
          >
            <div className="flex items-start justify-between">
              <div>
                <p className="text-slate-400 text-sm mb-2">{widget.title}</p>
                <p className="text-3xl font-bold text-white">{widget.value}</p>
              </div>
              <span className="text-4xl">{widget.icon}</span>
            </div>
          </div>
        ))}
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div className="bg-slate-800 border border-slate-700 rounded-lg p-6">
          <h3 className="text-xl font-bold text-white mb-4">أحدث الزيارات</h3>
          <div className="space-y-3">
            {[
              { customer: 'أحمد علي', route: 'الطريق 1', time: '09:30 AM' },
              { customer: 'فاطمة محمد', route: 'الطريق 2', time: '10:15 AM' },
              { customer: 'محمد سالم', route: 'الطريق 1', time: '11:00 AM' },
            ].map((visit, idx) => (
              <div
                key={idx}
                className="flex items-center justify-between p-3 bg-slate-700/50 rounded"
              >
                <div>
                  <p className="font-medium text-white">{visit.customer}</p>
                  <p className="text-sm text-slate-400">{visit.route}</p>
                </div>
                <span className="text-slate-400">{visit.time}</span>
              </div>
            ))}
          </div>
        </div>

        <div className="bg-slate-800 border border-slate-700 rounded-lg p-6">
          <h3 className="text-xl font-bold text-white mb-4">التنبيهات المهمة</h3>
          <div className="space-y-3">
            {[
              { alert: 'عميل متأخر في الدفع', status: 'critical', days: '30+ يوم' },
              { alert: 'مخزون منخفض', status: 'warning', days: '5 وحدات' },
              { alert: 'شكوى جديدة', status: 'info', days: 'منذ ساعة' },
            ].map((item, idx) => (
              <div
                key={idx}
                className={`flex items-center gap-3 p-3 rounded ${
                  item.status === 'critical'
                    ? 'bg-red-500/20 border border-red-500/30'
                    : item.status === 'warning'
                    ? 'bg-yellow-500/20 border border-yellow-500/30'
                    : 'bg-blue-500/20 border border-blue-500/30'
                }`}
              >
                <span className="text-2xl">
                  {item.status === 'critical' ? '🔴' : item.status === 'warning' ? '🟡' : '🔵'}
                </span>
                <div>
                  <p className="font-medium text-white">{item.alert}</p>
                  <p className="text-sm text-slate-300">{item.days}</p>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
}
