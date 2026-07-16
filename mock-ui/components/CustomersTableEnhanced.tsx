'use client';

import { useState } from 'react';
import { Eye, Edit2, Trash2, ChevronDown, CheckSquare, Square } from 'lucide-react';

interface Customer {
  id: string;
  nameAr: string;
  nameEn: string;
  phone: string;
  route: string;
  status: string;
  active: boolean;
  healthScore: number;
  lastInteraction: string;
  totalSpent: string;
  overdue: boolean;
}

export default function CustomersTableEnhanced() {
  const [customers] = useState<Customer[]>([
    { id: 'C001', nameAr: 'أحمد علي', nameEn: 'Ahmed Ali', phone: '+966501234567', route: 'الطريق 1', status: 'معتمد', active: true, healthScore: 92, lastInteraction: 'اليوم', totalSpent: '450K SAR', overdue: false },
    { id: 'C002', nameAr: 'فاطمة محمد', nameEn: 'Fatima Mohammad', phone: '+966509876543', route: 'الطريق 2', status: 'قيد الانتظار', active: true, healthScore: 78, lastInteraction: '3 أيام', totalSpent: '220K SAR', overdue: false },
    { id: 'C003', nameAr: 'محمد سالم', nameEn: 'Mohammad Salem', phone: '+966505555555', route: 'الطريق 1', status: 'معتمد', active: false, healthScore: 45, lastInteraction: '30 يوم', totalSpent: '890K SAR', overdue: true },
    { id: 'C004', nameAr: 'نورا خالد', nameEn: 'Nora Khaled', phone: '+966502222222', route: 'الطريق 3', status: 'معتمد', active: true, healthScore: 88, lastInteraction: 'اليوم', totalSpent: '310K SAR', overdue: false },
    { id: 'C005', nameAr: 'علي محمود', nameEn: 'Ali Mahmoud', phone: '+966503333333', route: 'الطريق 2', status: 'معتمد', active: true, healthScore: 95, lastInteraction: '2 يوم', totalSpent: '1.2M SAR', overdue: false },
  ]);

  const [selectedRows, setSelectedRows] = useState<Set<string>>(new Set());
  const [expandedRow, setExpandedRow] = useState<string | null>(null);

  const toggleRowSelection = (id: string) => {
    const newSelected = new Set(selectedRows);
    if (newSelected.has(id)) {
      newSelected.delete(id);
    } else {
      newSelected.add(id);
    }
    setSelectedRows(newSelected);
  };

  const toggleAllRows = () => {
    if (selectedRows.size === customers.length) {
      setSelectedRows(new Set());
    } else {
      setSelectedRows(new Set(customers.map((c) => c.id)));
    }
  };

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

  const getHealthScoreColor = (score: number) => {
    if (score >= 85) return 'text-green-400';
    if (score >= 70) return 'text-blue-400';
    if (score >= 50) return 'text-yellow-400';
    return 'text-red-400';
  };

  const getHealthScoreBg = (score: number) => {
    if (score >= 85) return 'bg-green-500/20';
    if (score >= 70) return 'bg-blue-500/20';
    if (score >= 50) return 'bg-yellow-500/20';
    return 'bg-red-500/20';
  };

  return (
    <div className="p-8">
      <div className="flex items-center justify-between mb-6">
        <h2 className="text-3xl font-bold text-white">العملاء</h2>
        <button className="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition font-medium">
          + إضافة عميل جديد
        </button>
      </div>

      {/* Bulk Actions Bar */}
      {selectedRows.size > 0 && (
        <div className="bg-blue-500/20 border border-blue-500/50 rounded-lg p-4 mb-6 flex items-center justify-between">
          <span className="text-blue-300 font-medium">{selectedRows.size} عميل مختار</span>
          <div className="flex gap-2">
            <button className="px-3 py-1 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded transition">تصدير</button>
            <button className="px-3 py-1 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded transition">تعيين طريق</button>
            <button className="px-3 py-1 text-sm bg-red-600 hover:bg-red-700 text-white rounded transition">حذف</button>
          </div>
        </div>
      )}

      <div className="bg-slate-800 border border-slate-700 rounded-lg overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead className="bg-slate-700/50 border-b border-slate-600">
              <tr>
                <th className="px-4 py-4 text-center w-10">
                  <button onClick={toggleAllRows} className="text-slate-400 hover:text-white">
                    {selectedRows.size === customers.length ? (
                      <CheckSquare className="w-5 h-5" />
                    ) : (
                      <Square className="w-5 h-5" />
                    )}
                  </button>
                </th>
                <th className="px-6 py-4 text-right text-sm font-semibold text-slate-300">الكود</th>
                <th className="px-6 py-4 text-right text-sm font-semibold text-slate-300">الاسم</th>
                <th className="px-6 py-4 text-right text-sm font-semibold text-slate-300">الهاتف</th>
                <th className="px-6 py-4 text-right text-sm font-semibold text-slate-300">خط السير</th>
                <th className="px-6 py-4 text-right text-sm font-semibold text-slate-300">الحالة</th>
                <th className="px-6 py-4 text-right text-sm font-semibold text-slate-300">صحة العميل</th>
                <th className="px-6 py-4 text-right text-sm font-semibold text-slate-300">الإنفاق</th>
                <th className="px-6 py-4 text-right text-sm font-semibold text-slate-300">الإجراءات</th>
              </tr>
            </thead>
            <tbody>
              {customers.map((customer) => (
                <div key={customer.id}>
                  <tr className={`border-b border-slate-700/50 hover:bg-slate-700/30 transition ${selectedRows.has(customer.id) ? 'bg-slate-700/50' : ''}`}>
                    <td className="px-4 py-4 text-center">
                      <button onClick={() => toggleRowSelection(customer.id)} className="text-slate-400 hover:text-white">
                        {selectedRows.has(customer.id) ? (
                          <CheckSquare className="w-5 h-5 text-blue-500" />
                        ) : (
                          <Square className="w-5 h-5" />
                        )}
                      </button>
                    </td>
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
                      <div className={`inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold ${getHealthScoreBg(customer.healthScore)} ${getHealthScoreColor(customer.healthScore)}`}>
                        <span>{customer.healthScore}</span>
                        <div className="w-12 h-1.5 bg-slate-600 rounded-full overflow-hidden">
                          <div
                            className={`h-full ${customer.healthScore >= 85 ? 'bg-green-400' : customer.healthScore >= 70 ? 'bg-blue-400' : customer.healthScore >= 50 ? 'bg-yellow-400' : 'bg-red-400'}`}
                            style={{ width: `${customer.healthScore}%` }}
                          />
                        </div>
                      </div>
                    </td>
                    <td className="px-6 py-4 text-sm">
                      <div className="text-white font-medium">{customer.totalSpent}</div>
                      {customer.overdue && (
                        <div className="text-xs text-red-400 font-semibold">متأخر الدفع ⚠️</div>
                      )}
                    </td>
                    <td className="px-6 py-4 text-sm">
                      <div className="flex items-center gap-1">
                        <button
                          onClick={() => setExpandedRow(expandedRow === customer.id ? null : customer.id)}
                          className="p-1.5 text-slate-400 hover:bg-slate-700 rounded transition"
                        >
                          <ChevronDown className="w-5 h-5" />
                        </button>
                        <button className="p-1.5 text-blue-400 hover:bg-blue-500/20 rounded transition">
                          <Eye className="w-5 h-5" />
                        </button>
                        <button className="p-1.5 text-blue-400 hover:bg-blue-500/20 rounded transition">
                          <Edit2 className="w-5 h-5" />
                        </button>
                        <button className="p-1.5 text-red-400 hover:bg-red-500/20 rounded transition">
                          <Trash2 className="w-5 h-5" />
                        </button>
                      </div>
                    </td>
                  </tr>

                  {/* Expandable Row Details */}
                  {expandedRow === customer.id && (
                    <tr className="bg-slate-700/30 border-b border-slate-700/50">
                      <td colSpan={9} className="px-6 py-4">
                        <div className="grid grid-cols-4 gap-4">
                          <div>
                            <p className="text-slate-400 text-xs">آخر تفاعل</p>
                            <p className="text-white font-medium">{customer.lastInteraction}</p>
                          </div>
                          <div>
                            <p className="text-slate-400 text-xs">الحد الائتماني</p>
                            <p className="text-white font-medium">500K SAR</p>
                          </div>
                          <div>
                            <p className="text-slate-400 text-xs">الرصيد المستحق</p>
                            <p className={`font-medium ${customer.overdue ? 'text-red-400' : 'text-green-400'}`}>85K SAR</p>
                          </div>
                          <div>
                            <p className="text-slate-400 text-xs">البريد الإلكتروني</p>
                            <p className="text-blue-400 font-medium">customer@email.com</p>
                          </div>
                        </div>
                      </td>
                    </tr>
                  )}
                </div>
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
