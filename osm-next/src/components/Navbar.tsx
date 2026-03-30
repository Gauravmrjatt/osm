'use client';

import Link from 'next/link';
import { Settings } from 'lucide-react';

export default function Navbar({ isAdmin = false }: { isAdmin?: boolean }) {
  return (
    <nav className="sticky top-0 z-50 backdrop-blur-xl border-b border-[var(--border)] bg-[rgba(11,15,20,0.9)] h-16 flex items-center justify-between px-4">
      <Link href="/" className="font-extrabold text-xl tracking-tight">
        OS<span className="text-[var(--primary-light)]">M</span>
      </Link>
      {isAdmin && (
        <Link 
          href="/admin" 
          className="w-10 h-10 rounded-full border border-white/15 flex items-center justify-center text-[var(--text-sub)] hover:border-[var(--primary)] hover:text-[var(--primary)] transition-all"
        >
          <Settings className="w-4 h-4" />
        </Link>
      )}
    </nav>
  );
}
