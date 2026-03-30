'use client';

import Link from 'next/link';
import { Users, ArrowRight } from 'lucide-react';
import { formatNumber, isExpired } from '@/lib/utils';

interface OfferCardProps {
  offer: {
    _id: string;
    title: string;
    brand_name: string;
    brand_emoji: string;
    logo_image?: string;
    category: string;
    max_cashback: number;
    cashback_rate: number;
    cashback_type: string;
    claimed_count: number;
    expiry_date?: string;
  };
  index: number;
}

export default function OfferCard({ offer, index }: OfferCardProps) {
  const expired = offer.expiry_date ? isExpired(offer.expiry_date) : false;
  const cashbackText = offer.cashback_type === 'flat' 
    ? `₹${formatNumber(offer.max_cashback)}` 
    : `${offer.cashback_rate}%`;

  return (
    <Link 
      href={`/offer/${offer._id}`}
      className={`offer-card block bg-[var(--bg-card)] rounded-[var(--radius)] p-3.5 flex items-center gap-3 border border-[var(--border)] transition-all duration-250 hover:-translate-y-0.5 hover:border-[rgba(30,107,255,0.3)] hover:shadow-[var(--shadow)] animate-fadeUp ${expired ? 'opacity-50' : ''}`}
      style={{ animationDelay: `${index * 0.02}s` }}
    >
      {!expired && (
        <span className="absolute top-2.5 right-2.5 text-[0.6rem] font-bold text-[var(--green)] flex items-center gap-1">
          <span className="w-1.5 h-1.5 bg-[var(--green)] rounded-full"></span>
          LIVE
        </span>
      )}
      
      <div className="w-12 h-12 rounded-xl overflow-hidden flex-shrink-0 flex items-center justify-center bg-white/5">
        {offer.logo_image ? (
          <img 
            src={`/uploads/${offer.logo_image}`} 
            alt={offer.brand_name}
            className="w-full h-full object-cover"
          />
        ) : (
          <span className="text-2xl">{offer.brand_emoji}</span>
        )}
      </div>
      
      <div className="flex-1 min-w-0">
        <div className="text-[0.7rem] font-semibold text-[var(--text-sub)] mb-0.5">
          {offer.brand_name}
        </div>
        <div className="text-[0.85rem] font-semibold text-[var(--text)] whitespace-nowrap overflow-hidden text-ellipsis mb-1.5">
          {offer.title}
        </div>
        <div className="flex items-center gap-2 flex-wrap">
          <span className="text-[0.68rem] text-[var(--text-sub)] flex items-center gap-0.5">
            <Users className="w-2.5 h-2.5" />
            {formatNumber(offer.claimed_count)}
          </span>
          <span className="text-[0.65rem] font-bold bg-[rgba(0,210,106,0.12)] text-[var(--green)] px-2 py-0.5 rounded-md">
            {cashbackText} Cashback
          </span>
        </div>
      </div>
      
      <div className="flex-shrink-0">
        <div className="px-4 py-2.5 rounded-[var(--radius-pill)] bg-gradient-to-r from-[var(--primary)] to-[var(--primary-light)] text-white text-[0.7rem] font-bold whitespace-nowrap shadow-[var(--glow)]">
          Claim Now
        </div>
      </div>

      <style jsx>{`
        .offer-card {
          position: relative;
          text-decoration: none;
          color: inherit;
        }
      `}</style>
    </Link>
  );
}
