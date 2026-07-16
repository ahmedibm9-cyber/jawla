interface SidebarProps {
  currentPage: string;
  setCurrentPage: (page: string) => void;
}

export default function Sidebar({ currentPage, setCurrentPage }: SidebarProps) {
  const menuItems = [
    { id: 'dashboard', label: 'لوحة التحكم', icon: '📊' },
    { id: 'customers', label: 'العملاء', icon: '👥' },
    { id: 'invoices', label: 'الفواتير', icon: '📋' },
    { id: 'visits', label: 'الزيارات', icon: '📍' },
    { id: 'complaints', label: 'الشكاوى', icon: '⚠️' },
    { id: 'inventory', label: 'المخزون', icon: '📦' },
  ];

  return (
    <aside className="w-64 bg-slate-950 border-l border-slate-700 p-6 flex flex-col">
      <div className="mb-8">
        <h1 className="text-3xl font-bold text-blue-500">Jawla</h1>
        <p className="text-sm text-slate-400">نظام إدارة العلاقات</p>
      </div>

      <nav className="space-y-2 flex-1">
        {menuItems.map((item) => (
          <button
            key={item.id}
            onClick={() => setCurrentPage(item.id)}
            className={`w-full flex items-center gap-3 px-4 py-3 rounded-lg transition ${
              currentPage === item.id
                ? 'bg-blue-600 text-white'
                : 'text-slate-300 hover:bg-slate-800'
            }`}
          >
            <span className="text-xl">{item.icon}</span>
            <span className="font-medium">{item.label}</span>
          </button>
        ))}
      </nav>

      <div className="pt-6 border-t border-slate-700">
        <button className="w-full flex items-center gap-3 px-4 py-3 text-slate-300 hover:bg-slate-800 rounded-lg transition">
          <span className="text-xl">👤</span>
          <span>حسابي</span>
        </button>
      </div>
    </aside>
  );
}
