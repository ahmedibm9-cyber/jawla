'use client';

import { useState } from 'react';
import TopNavbar from '@/components/TopNavbar';
import Dashboard from '@/components/Dashboard';
import CustomersTableEnhanced from '@/components/CustomersTableEnhanced';
import Sidebar from '@/components/Sidebar';

export default function Home() {
  const [currentPage, setCurrentPage] = useState('dashboard');

  return (
    <div className="flex h-screen bg-slate-950">
      <Sidebar currentPage={currentPage} setCurrentPage={setCurrentPage} />
      <div className="flex-1 flex flex-col overflow-hidden">
        <TopNavbar />
        <main className="flex-1 overflow-auto bg-slate-900">
          {currentPage === 'dashboard' && <Dashboard />}
          {currentPage === 'customers' && <CustomersTableEnhanced />}
        </main>
      </div>
    </div>
  );
}
