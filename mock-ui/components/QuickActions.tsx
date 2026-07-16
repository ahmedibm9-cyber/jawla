'use client';

import React from 'react';
import { Plus, FileText, Users, MapPin, AlertCircle } from 'lucide-react';

interface QuickActionProps {
  icon: React.ReactNode;
  label: string;
  sublabel?: string;
  onClick?: () => void;
}

function QuickActionButton({ icon, label, sublabel, onClick }: QuickActionProps) {
  return (
    <button
      onClick={onClick}
      className="flex flex-col items-center gap-3 p-4 bg-slate-700 hover:bg-slate-600 border border-slate-600 hover:border-blue-500 rounded-lg transition group"
    >
      <div className="p-3 bg-blue-500/20 rounded-lg group-hover:bg-blue-500/30 transition">
        {icon}
      </div>
      <div className="text-center">
        <p className="text-white font-semibold text-sm">{label}</p>
        {sublabel && <p className="text-slate-400 text-xs">{sublabel}</p>}
      </div>
    </button>
  );
}

export default function QuickActions() {
  return (
    <div className="bg-slate-800 border border-slate-700 rounded-lg p-6 mb-8">
      <h2 className="text-white text-lg font-bold mb-6">إجراءات سريعة</h2>
      
      <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
        <QuickActionButton
          icon={<Plus className="w-6 h-6 text-blue-400" />}
          label="عميل جديد"
          sublabel="إضافة عميل"
        />
        <QuickActionButton
          icon={<FileText className="w-6 h-6 text-green-400" />}
          label="فاتورة جديدة"
          sublabel="إنشاء فاتورة"
        />
        <QuickActionButton
          icon={<MapPin className="w-6 h-6 text-purple-400" />}
          label="زيارة جديدة"
          sublabel="جدولة زيارة"
        />
        <QuickActionButton
          icon={<Users className="w-6 h-6 text-amber-400" />}
          label="طلب سعر"
          sublabel="إنشاء عرض"
        />
        <QuickActionButton
          icon={<AlertCircle className="w-6 h-6 text-red-400" />}
          label="تقارير"
          sublabel="عرض التقارير"
        />
      </div>
    </div>
  );
}
