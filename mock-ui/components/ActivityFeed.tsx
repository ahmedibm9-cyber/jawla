'use client';

import React from 'react';
import { Clock, User, FileText, CheckCircle, AlertCircle } from 'lucide-react';

interface Activity {
  id: string;
  type: 'invoice' | 'customer' | 'visit' | 'approval' | 'alert';
  title: string;
  description: string;
  user: string;
  timestamp: string;
  icon: React.ReactNode;
}

const activities: Activity[] = [
  {
    id: '1',
    type: 'invoice',
    title: 'فاتورة جديدة تم إنشاؤها',
    description: 'فاتورة رقم INV-2024-0145 للعميل أحمد علي',
    user: 'محمد السلمان',
    timestamp: 'منذ 5 دقائق',
    icon: <FileText className="w-5 h-5 text-blue-400" />,
  },
  {
    id: '2',
    type: 'customer',
    title: 'عميل جديد تم تسجيله',
    description: 'تم تسجيل العميل فاطمة محمد من الرياض',
    user: 'سارة الدعيع',
    timestamp: 'منذ 15 دقيقة',
    icon: <User className="w-5 h-5 text-green-400" />,
  },
  {
    id: '3',
    type: 'approval',
    title: 'موافقة على طلب سعر',
    description: 'تمت الموافقة على طلب السعر QT-2024-089',
    user: 'النظام',
    timestamp: 'منذ 45 دقيقة',
    icon: <CheckCircle className="w-5 h-5 text-green-400" />,
  },
  {
    id: '4',
    type: 'alert',
    title: 'تحذير: فاتورة متأخرة',
    description: 'الفاتورة INV-2024-0100 متأخرة 15 يوم',
    user: 'النظام',
    timestamp: 'منذ ساعة',
    icon: <AlertCircle className="w-5 h-5 text-red-400" />,
  },
];

export default function ActivityFeed() {
  return (
    <div className="bg-slate-800 border border-slate-700 rounded-lg p-6">
      <div className="flex items-center justify-between mb-6">
        <h2 className="text-white text-lg font-bold">نشاط حديث</h2>
        <a href="#" className="text-blue-400 hover:text-blue-300 text-sm">عرض الكل →</a>
      </div>

      <div className="space-y-4">
        {activities.map((activity, index) => (
          <div key={activity.id} className="flex gap-4">
            {/* Timeline dot */}
            <div className="flex flex-col items-center">
              <div className="w-10 h-10 rounded-full bg-slate-700 flex items-center justify-center">
                {activity.icon}
              </div>
              {index !== activities.length - 1 && (
                <div className="w-1 h-12 bg-slate-700 my-2" />
              )}
            </div>

            {/* Activity content */}
            <div className="flex-1 pb-4">
              <div className="flex items-start justify-between mb-2">
                <div>
                  <p className="text-white font-semibold">{activity.title}</p>
                  <p className="text-slate-300 text-sm">{activity.description}</p>
                </div>
                <span className="text-slate-400 text-xs flex items-center gap-1 whitespace-nowrap">
                  <Clock className="w-3 h-3" />
                  {activity.timestamp}
                </span>
              </div>
              <p className="text-slate-400 text-xs">بواسطة: {activity.user}</p>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
