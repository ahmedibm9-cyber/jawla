'use client';

import { useState } from 'react';

export default function CustomersTable() {
  const [customers] = useState([
    { id: 'C001', nameAr: 'أحمد علي', nameEn: 'Ahmed Ali', phone: '+966501234567', route: 'الطريق 1', status: 'معتمد', active: true },
    { id: 'C002', nameAr: 'فاطمة محمد', nameEn: 'Fatima Mohammad', phone: '+966509876543', route: 'الطريق 2', status: 'قيد الانتظار', active: true },
    { id: 'C003', nameAr: 'محمد سالم', nameEn: 'Mohammad Salem', phone: '+966505555555', route: 'الطريق 1', status: 'معتمد', active: false },
    { id: 'C004', nameAr: 'نورا خالد', nameEn: 'Nora Khaled', phone: '+966502222222', route: 'الطريق 3', status: 'مرفوض', active: true },
    { id: 'C005', nameAr: 'علي محمود', nameEn: 'Ali Mahmoud', phone: '+966503333333', route: 'الطريق 2', status: 'معتمد', active: true },
  ]);

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'معتمد':
        return 'bg-green-500/20 text-green-300 border-green-500/30';
      case 'قيد الانتظار':
        return 'bg-yellow-500/20 text-yellow-300 border-yellow-500/30';
      default:
        return 'bg-red-500/20 text-red-300 border-red-500/30';
    }
  };

  return (
    <div className="p-8">
      <div className="flex items-center justify-between mb-8">
        <h2 className="text-3xl font-bold text-white">العملاء</h2>
        <button className="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
          + إضافة عميل جديد
        </button>
      </div>

      <div className="bg-slate-800 border border-slate-700 rounded-lg overflow-hidden">
        <div className="p-4 border-b border-slate-700 flex gap-3">
          <input
            type="text"
            placeholder="بحث..."
            className="flex-1 px-3 py-2 bg-slate-700 border border-slate-600 rounded text-white placeholder-slate-400 focus:outline-none focus:border-blue-500"
          />
          <select className="px-3 py-2 bg-slate-700 border border-slate-600 rounded text-white focus:outline-none focus:border-blue-500">
            <option>الحالة</option>
            <option>معتمد</option>
            <option>قيد الانتظار</option>
            <option>مرفوض</option>
          </select>
          <select className="px-3 py-2 bg-slate-700 border border-slate-600 rounded text-white focus:outline-none focus:border-blue-500">
            <option>خط السير</option>
            <option>الطريق 1</option>
            <option>الطريق 2</option>
            <option>الطريق 3</option>
          </select>
        </div>

        <div className="overflow-x-auto">
          <table className="w-full">
            <thead className="bg-slate-700/50 border-b border-slate-600">
              <tr>
                <th className="px-6 py-4 text-right text-sm font-semibold text-slate-300">الكود</th>
                <th className="px-6 py-4 text-right text-sm font-semibold text-slate-300">الاسم</th>
                <th className="px-6 py-4 text-right text-sm font-semibold text-slate-300">الهاتف</th>
                <th className="px-6 py-4 text-right text-sm font-semibold text-slate-300">خط السير</th>
                <th className="px-6 py-4 text-right text-sm font-semibold text-slate-300">الحالة</th>
                <th className="px-6 py-4 text-right text-sm font-semibold text-slate-300">نشط</th>
                <th className="px-6 py-4 text-right text-sm font-semibold text-slate-300">الإجراءات</th>
              </tr>
            </thead>
            <tbody>
              {customers.map((customer, idx) => (
                <tr key={idx} className="border-b border-slate-700/50 hover:bg-slate-700/30 transition">
                  <td className="px-6 py-4 text-sm font-mono text-slate-300">{customer.id}</td>
                  <td className="px-6 py-4 text-sm">
                    <div className="text-white font-medium">{customer.nameAr}</div>
                    <div className="text-xs text-slate-400">{customer.nameEn}</div>
                  </td>
                  <td className="px-6 py-4 text-sm text-slate-300">{customer.phone}</td>
                  <td className="px-6 py-4 text-sm text-slate-300">{customer.route}</td>
                  <td className="px-6 py-4 text-sm">
                    <span className={`inline-flex items-center px-3 py-1 rounded-full text-xs font-medium border ${getStatusColor(customer.status)}`}>
                      {customer.status}
                    </span>
                  </td>
                  <td className="px-6 py-4 text-sm">
                    <span className={`inline-flex items-center justify-center w-6 h-6 rounded ${customer.active ? 'bg-green-500/20 text-green-400' : 'bg-slate-600 text-slate-400'}`}>
                      {customer.active ? '✓' : '✕'}
                    </span>
                  </td>
                  <td className="px-6 py-4 text-sm flex gap-2">
                    <button className="px-2 py-1 text-xs bg-blue-600/20 text-blue-300 hover:bg-blue-600/30 rounded transition">
                      تعديل
                    </button>
                    <button className="px-2 py-1 text-xs bg-red-600/20 text-red-300 hover:bg-red-600/30 rounded transition">
                      حذف
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>

        <div className="px-6 py-4 border-t border-slate-700 flex items-center justify-between text-sm text-slate-400">
          <span>عرض 1-5 من 42 عميل</span>
          <div className="flex gap-2">
            <button className="px-3 py-1 bg-slate-700 hover:bg-slate-600 rounded transition">السابق</button>
            <button className="px-3 py-1 bg-blue-600 text-white rounded">1</button>
            <button className="px-3 py-1 bg-slate-700 hover:bg-slate-600 rounded transition">2</button>
            <button className="px-3 py-1 bg-slate-700 hover:bg-slate-600 rounded transition">التالي</button>
          </div>
        </div>
      </div>
    </div>
  );
}
