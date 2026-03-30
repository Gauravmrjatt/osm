'use client';

import Link from 'next/link';

interface FeaturedOffer {
  _id: string;
  title: string;
  brand_emoji: string;
  logo_image?: string;
  max_cashback: number;
  cashback_rate: number;
  cashback_type: string;
}

export default function FeaturedSection({ offers }: { offers: FeaturedOffer[] }) {
  if (!offers || offers.length === 0) return null;

  return (
    <div className="mb-6">
      <div className="flex items-center justify-between mb-3">
        <h3 className="text-[0.95rem] font-bold text-[var(--text)]">Featured Offers</h3>
        <span className="text-[0.65rem] text-[var(--primary-light)] font-semibold">🔥 HOT</span>
      </div>
      <div className="flex gap-3 overflow-x-auto scrollbar-hide pb-1" style={{ scrollSnapType: 'x mandatory' }}>
        {offers.map((offer) => {
          const cashbackText = offer.cashback_type === 'flat' 
            ? `₹${offer.max_cashback}` 
            : `${offer.cashback_rate}%`;

          return (
            <Link
              key={offer._id}
              href={`/offer/${offer._id}`}
              style={{ flex: '0 0 160px', scrollSnapAlign: 'start' }}
              className="block bg-[var(--bg-card)] rounded-[var(--radius)] p-3.5 border border-[var(--border)]"
            >
              <div className="w-10 h-10 rounded-xl overflow-hidden mb-2.5">
                {offer.logo_image ? (
                  <img 
                    src={`/uploads/${offer.logo_image}`} 
                    alt={offer.title}
                    className="w-full h-full object-cover"
                  />
                ) : (
                  <div className="w-full h-full flex items-center justify-center bg-white/5 text-2xl">
                    {offer.brand_emoji}
                  </div>
                )}
              </div>
              <div className="text-[0.8rem] font-semibold mb-1.5 whitespace-nowrap overflow-hidden text-ellipsis">
                {offer.title}
              </div>
              <div className="text-[0.65rem] font-bold bg-[rgba(0,210,106,0.12)] text-[var(--green)] px-2 py-1 rounded-md inline-block">
                {cashbackText} Cashback
              </div>
            </Link>
          );
        })}
      </div>
    </div>
  );
}
