'use client';

import React, { useState } from 'react';
import { Search, Filter, X } from 'lucide-react';

interface FilterOption {
  label: string;
  value: string;
}

interface AdvancedSearchProps {
  onSearch?: (query: string) => void;
  onFilter?: (filters: Record<string, string>) => void;
  filters?: {
    status?: FilterOption[];
    route?: FilterOption[];
    paymentStatus?: FilterOption[];
  };
}

export default function AdvancedSearch({ onSearch, onFilter, filters }: AdvancedSearchProps) {
  const [searchQuery, setSearchQuery] = useState('');
  const [activeFilters, setActiveFilters] = useState<Record<string, string>>({});
  const [showAdvanced, setShowAdvanced] = useState(false);

  const handleSearch = (value: string) => {
    setSearchQuery(value);
    onSearch?.(value);
  };

  const handleFilterChange = (filterKey: string, value: string) => {
    const newFilters = { ...activeFilters, [filterKey]: value };
    if (!value) {
      delete newFilters[filterKey];
    }
    setActiveFilters(newFilters);
    onFilter?.(newFilters);
  };

  const clearFilters = () => {
    setActiveFilters({});
    setSearchQuery('');
    onFilter?.({});
    onSearch?.('');
  };

  const activeFilterCount = Object.keys(activeFilters).length;

  return (
    <div className="bg-slate-800 border border-slate-700 rounded-lg p-6 mb-6">
      {/* Main Search Bar */}
      <div className="flex gap-3 mb-4">
        <div className="flex-1 relative">
          <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-400 w-5 h-5" />
          <input
            type="text"
            placeholder="ابحث بالاسم أو الكود أو الهاتف..."
            value={searchQuery}
            onChange={(e) => handleSearch(e.target.value)}
            className="w-full bg-slate-700 text-white placeholder-slate-400 pl-10 pr-4 py-3 rounded-lg border border-slate-600 focus:border-blue-500 focus:outline-none transition"
          />
        </div>
        <button
          onClick={() => setShowAdvanced(!showAdvanced)}
          className={`px-4 py-3 rounded-lg border transition flex items-center gap-2 ${
            showAdvanced
              ? 'bg-blue-500/20 border-blue-500 text-blue-300'
              : 'bg-slate-700 border-slate-600 text-slate-300 hover:border-slate-500'
          }`}
        >
          <Filter className="w-5 h-5" />
          <span>فلاتر</span>
          {activeFilterCount > 0 && (
            <span className="bg-blue-500 text-white text-xs font-bold px-2 py-0.5 rounded-full">
              {activeFilterCount}
            </span>
          )}
        </button>
      </div>

      {/* Advanced Filters */}
      {showAdvanced && filters && (
        <div className="space-y-4 pt-4 border-t border-slate-700">
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            {/* Status Filter */}
            {filters.status && (
              <div>
                <label className="block text-slate-300 text-sm font-medium mb-2">الحالة</label>
                <select
                  value={activeFilters.status || ''}
                  onChange={(e) => handleFilterChange('status', e.target.value)}
                  className="w-full bg-slate-700 text-white px-4 py-2 rounded-lg border border-slate-600 focus:border-blue-500 focus:outline-none"
                >
                  <option value="">كل الحالات</option>
                  {filters.status.map((option) => (
                    <option key={option.value} value={option.value}>
                      {option.label}
                    </option>
                  ))}
                </select>
              </div>
            )}

            {/* Route Filter */}
            {filters.route && (
              <div>
                <label className="block text-slate-300 text-sm font-medium mb-2">خط السير</label>
                <select
                  value={activeFilters.route || ''}
                  onChange={(e) => handleFilterChange('route', e.target.value)}
                  className="w-full bg-slate-700 text-white px-4 py-2 rounded-lg border border-slate-600 focus:border-blue-500 focus:outline-none"
                >
                  <option value="">كل الخطوط</option>
                  {filters.route.map((option) => (
                    <option key={option.value} value={option.value}>
                      {option.label}
                    </option>
                  ))}
                </select>
              </div>
            )}

            {/* Payment Status Filter */}
            {filters.paymentStatus && (
              <div>
                <label className="block text-slate-300 text-sm font-medium mb-2">حالة الدفع</label>
                <select
                  value={activeFilters.paymentStatus || ''}
                  onChange={(e) => handleFilterChange('paymentStatus', e.target.value)}
                  className="w-full bg-slate-700 text-white px-4 py-2 rounded-lg border border-slate-600 focus:border-blue-500 focus:outline-none"
                >
                  <option value="">كل الحالات</option>
                  {filters.paymentStatus.map((option) => (
                    <option key={option.value} value={option.value}>
                      {option.label}
                    </option>
                  ))}
                </select>
              </div>
            )}
          </div>

          {activeFilterCount > 0 && (
            <div className="flex justify-end">
              <button
                onClick={clearFilters}
                className="flex items-center gap-2 text-red-400 hover:text-red-300 text-sm"
              >
                <X className="w-4 h-4" />
                مسح الفلاتر
              </button>
            </div>
          )}
        </div>
      )}
    </div>
  );
}
