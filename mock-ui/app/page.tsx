'use client';

import { useState } from 'react';
import Dashboard from '@/components/Dashboard';
import CustomersTable from '@/components/CustomersTable';
import Sidebar from '@/components/Sidebar';

export default function Home() {
  const [currentPage, setCurrentPage] = useState('dashboard');

  return (
    <div className="flex h-screen bg-slate-950">
      <Sidebar currentPage={currentPage} setCurrentPage={setCurrentPage} />
      <main className="flex-1 overflow-auto bg-slate-900">
        {currentPage === 'dashboard' && <Dashboard />}
        {currentPage === 'customers' && <CustomersTable />}
      </main>
    </div>
  );
}
