'use client';

import Link from 'next/link';
import { Settings, Menu, X } from 'lucide-react';
import { useState } from 'react';
import { ThemeToggle } from './theme-toggle';
import { Button } from './ui/button';

export default function Navbar({ isAdmin = false }: { isAdmin?: boolean }) {
  const [menuOpen, setMenuOpen] = useState(false);

  return (
    <nav className="sticky top-0 z-50 backdrop-blur-xl border-b border-[var(--border)] bg-[var(--card)] h-16 flex items-center justify-between px-4">
      <Link href="/" className="font-extrabold text-xl tracking-tight">
        OS<span className="text-[var(--primary-light)]">M</span>
      </Link>
      
      <div className="flex items-center gap-2">
        <ThemeToggle />
        
        {isAdmin && (
          <Link href="/admin">
            <Button variant="ghost" size="icon">
              <Settings className="w-4 h-4" />
            </Button>
          </Link>
        )}
        
       
      </div>
    </nav>
  );
}
