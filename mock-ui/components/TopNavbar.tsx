'use client';

import React, { useState } from 'react';
import { Bell, Search, Settings, LogOut, ChevronDown } from 'lucide-react';

interface TopNavbarProps {
  userName?: string;
  userRole?: string;
}

export default function TopNavbar({ userName = 'أحمد علي', userRole = 'مدير المبيعات' }: TopNavbarProps) {
  const [notificationCount] = useState(3);
  const [searchQuery, setSearchQuery] = useState('');
  const [showProfileMenu, setShowProfileMenu] = useState(false);

  return (
    <div className="bg-slate-800 border-b border-slate-700 h-16 flex items-center justify-between px-6 sticky top-0 z-40 shadow-lg">
      {/* Left: Logo/Branding */}
      <div className="flex items-center gap-3">
        <div className="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center">
          <span className="text-white font-bold text-lg">J</span>
        </div>
        <div>
          <h1 className="text-white font-bold text-lg">Jawla</h1>
          <p className="text-slate-400 text-xs">Enterprise CRM</p>
        </div>
      </div>

      {/* Center: Global Search */}
      <div className="flex-1 max-w-md mx-auto px-4">
        <div className="relative">
          <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-400 w-5 h-5" />
          <input
            type="text"
            placeholder="ابحث عن عملاء، فواتير، أو طلبات..."
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            className="w-full bg-slate-700 text-white placeholder-slate-400 pl-10 pr-4 py-2.5 rounded-lg border border-slate-600 focus:border-blue-500 focus:outline-none transition"
          />
        </div>
      </div>

      {/* Right: Notifications, Settings, Profile */}
      <div className="flex items-center gap-4">
        {/* Notifications */}
        <button className="relative p-2 hover:bg-slate-700 rounded-lg transition">
          <Bell className="w-5 h-5 text-slate-300 hover:text-white" />
          {notificationCount > 0 && (
            <span className="absolute top-1 right-1 bg-red-500 text-white text-xs font-bold w-5 h-5 rounded-full flex items-center justify-center">
              {notificationCount}
            </span>
          )}
        </button>

        {/* Settings */}
        <button className="p-2 hover:bg-slate-700 rounded-lg transition">
          <Settings className="w-5 h-5 text-slate-300 hover:text-white" />
        </button>

        {/* Profile Menu */}
        <div className="relative">
          <button
            onClick={() => setShowProfileMenu(!showProfileMenu)}
            className="flex items-center gap-2 px-3 py-2 hover:bg-slate-700 rounded-lg transition"
          >
            <div className="w-8 h-8 bg-gradient-to-br from-green-500 to-blue-500 rounded-full flex items-center justify-center text-white text-sm font-bold">
              {userName.charAt(0)}
            </div>
            <div className="text-right hidden sm:block">
              <p className="text-white text-sm font-medium">{userName}</p>
              <p className="text-slate-400 text-xs">{userRole}</p>
            </div>
            <ChevronDown className="w-4 h-4 text-slate-400" />
          </button>

          {/* Profile Dropdown */}
          {showProfileMenu && (
            <div className="absolute right-0 mt-2 w-48 bg-slate-700 rounded-lg shadow-xl border border-slate-600 py-2 z-50">
              <button className="w-full text-right px-4 py-2 text-slate-300 hover:bg-slate-600 text-sm">الملف الشخصي</button>
              <button className="w-full text-right px-4 py-2 text-slate-300 hover:bg-slate-600 text-sm">الإعدادات</button>
              <hr className="border-slate-600 my-2" />
              <button className="w-full text-right px-4 py-2 text-red-400 hover:bg-slate-600 text-sm flex items-center gap-2 justify-end">
                <LogOut className="w-4 h-4" />
                تسجيل الخروج
              </button>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
