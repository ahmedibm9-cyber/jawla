'use client';

import React from 'react';
import { TrendingUp, TrendingDown, DollarSign, AlertCircle } from 'lucide-react';

interface MetricProps {
  label: string;
  value: string;
  trend?: number;
  icon: React.ReactNode;
  color: 'blue' | 'green' | 'amber' | 'red';
}

function MetricCard({ label, value, trend, icon, color }: MetricProps) {
  const colorClasses = {
    blue: 'bg-blue-900/30 border-blue-800 text-blue-300',
    green: 'bg-green-900/30 border-green-800 text-green-300',
    amber: 'bg-amber-900/30 border-amber-800 text-amber-300',
    red: 'bg-red-900/30 border-red-800 text-red-300',
  };

  const trendColor = trend && trend >= 0 ? 'text-green-400' : 'text-red-400';

  return (
    <div className={`${colorClasses[color]} border rounded-lg p-6 flex flex-col justify-between h-full`}>
      <div className="flex items-start justify-between mb-4">
        <div className="flex-1">
          <p className="text-slate-400 text-sm font-medium mb-2">{label}</p>
          <p className="text-white text-3xl font-bold">{value}</p>
        </div>
        <div className="p-3 bg-slate-700/50 rounded-lg">{icon}</div>
      </div>

      {trend !== undefined && (
        <div className={`flex items-center gap-1 text-sm font-semibold ${trendColor}`}>
          {trend >= 0 ? (
            <TrendingUp className="w-4 h-4" />
          ) : (
            <TrendingDown className="w-4 h-4" />
          )}
          <span>{Math.abs(trend)}% هذا الشهر</span>
        </div>
      )}
    </div>
  );
}

export default function FinancialMetrics() {
  return (
    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
      <MetricCard
        label="إجمالي الإيرادات"
        value="2.4M SAR"
        trend={12.5}
        icon={<DollarSign className="w-6 h-6 text-blue-400" />}
        color="blue"
      />
      <MetricCard
        label="المبيعات المعلقة"
        value="890K SAR"
        trend={8.2}
        icon={<TrendingUp className="w-6 h-6 text-green-400" />}
        color="green"
      />
      <MetricCard
        label="المبالغ المستحقة"
        value="445K SAR"
        trend={-3.1}
        icon={<AlertCircle className="w-6 h-6 text-amber-400" />}
        color="amber"
      />
      <MetricCard
        label="المبالغ المتأخرة"
        value="125K SAR"
        trend={-5.8}
        icon={<AlertCircle className="w-6 h-6 text-red-400" />}
        color="red"
      />
    </div>
  );
}
