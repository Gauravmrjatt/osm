'use client';

import { CheckCircle, TrendingUp, DollarSign, Shield, Zap, Headphones, Award } from 'lucide-react';
import { formatNumber } from '@/lib/utils';

interface Stats {
  total_claimed: number;
  active_offers: number;
  max_cashback: number;
}

export default function StatsSection({ stats }: { stats: Stats }) {
  return (
    <>
      <div className="grid grid-cols-3 gap-2.5 mb-6">
        <div className="bg-[var(--bg-card)] rounded-[var(--radius-sm)] p-4 text-center border border-[var(--border)]">
          <div className="w-8 h-8 mx-auto mb-1.5 bg-[rgba(30,107,255,0.15)] rounded-lg flex items-center justify-center text-[var(--primary-light)]">
            <CheckCircle className="w-4 h-4" />
          </div>
          <div className="text-[1.1rem] font-extrabold text-[var(--text)]">
            {formatNumber(stats?.total_claimed || 0)}
          </div>
          <div className="text-[0.6rem] text-[var(--text-sub)] mt-0.5">Total Claims</div>
        </div>
        <div className="bg-[var(--bg-card)] rounded-[var(--radius-sm)] p-4 text-center border border-[var(--border)]">
          <div className="w-8 h-8 mx-auto mb-1.5 bg-[rgba(30,107,255,0.15)] rounded-lg flex items-center justify-center text-[var(--primary-light)]">
            <TrendingUp className="w-4 h-4" />
          </div>
          <div className="text-[1.1rem] font-extrabold text-[var(--text)]">
            {stats?.active_offers || 0}
          </div>
          <div className="text-[0.6rem] text-[var(--text-sub)] mt-0.5">Active Offers</div>
        </div>
        <div className="bg-[var(--bg-card)] rounded-[var(--radius-sm)] p-4 text-center border border-[var(--border)]">
          <div className="w-8 h-8 mx-auto mb-1.5 bg-[rgba(30,107,255,0.15)] rounded-lg flex items-center justify-center text-[var(--primary-light)]">
            <DollarSign className="w-4 h-4" />
          </div>
          <div className="text-[1.1rem] font-extrabold text-[var(--text)]">
            ₹{formatNumber(stats?.max_cashback || 0)}
          </div>
          <div className="text-[0.6rem] text-[var(--text-sub)] mt-0.5">Max Earning</div>
        </div>
      </div>

      <div className="flex justify-center gap-5 flex-wrap py-5">
        <div className="flex items-center gap-1.5 text-[0.65rem] text-[var(--text-sub)]">
          <Shield className="w-3.5 h-3.5 text-[var(--primary-light)]" />
          100% Safe
        </div>
        <div className="flex items-center gap-1.5 text-[0.65rem] text-[var(--text-sub)]">
          <Zap className="w-3.5 h-3.5 text-[var(--primary-light)]" />
          Instant Payout
        </div>
        <div className="flex items-center gap-1.5 text-[0.65rem] text-[var(--text-sub)]">
          <Headphones className="w-3.5 h-3.5 text-[var(--primary-light)]" />
          24/7 Support
        </div>
        <div className="flex items-center gap-1.5 text-[0.65rem] text-[var(--text-sub)]">
          <Award className="w-3.5 h-3.5 text-[var(--primary-light)]" />
          Trusted
        </div>
      </div>
    </>
  );
}
