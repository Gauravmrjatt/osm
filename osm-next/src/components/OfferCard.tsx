'use client';

import Link from 'next/link';
import { Users, CheckCircle2 } from 'lucide-react';
import { formatNumber, isExpired } from '@/lib/utils';
import * as PricingCard from '@/components/ui/pricing-card';

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
    is_featured?: boolean;
    is_verified?: boolean;
    is_popular?: boolean;
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
      className={`block ${expired ? 'opacity-50 w-full' : ''}`}
      style={{ animationDelay: `${index * 0.02}s` }}
    >
      <PricingCard.Card className="hover:scale-[1.02] transition-transform duration-200 cursor-pointer w-full border-[var(--border-color)]/50 overflow-hidden">
        {/* Squeezed grid of icons with gradient mask */}
        <div className="absolute inset-0 opacity-20 pointer-events-none z-0" 
          style={{
            backgroundImage: `radial-gradient(circle, #9AA4B2 1px, transparent 1px)`,
            backgroundSize: '14px 14px',
            maskImage: 'linear-gradient(to top, black 50%, transparent 100%)',
            WebkitMaskImage: 'linear-gradient(to top, black 50%, transparent 100%)',
          }}
        />
        <div className="relative z-10">
        <PricingCard.Header glassEffect={true}>
          <PricingCard.Plan>
            <PricingCard.PlanName>
              <div className="w-8 h-8 rounded-lg overflow-hidden flex items-center justify-center bg-[var(--accent)] border border-[var(--border-color)]/50">
                {offer.logo_image ? (
                  <img 
                    src={`/api/files/${offer.logo_image}`} 
                    alt={offer.brand_name}
                    className="w-full h-full object-cover"
                  />
                ) : (
                  <span className="text-lg">{offer.brand_emoji}</span>
                )}
              </div>
              <div>
                <span className="text-[var(--foreground)]">{offer.brand_name}</span>
                {offer.is_verified && (
                  <CheckCircle2 className="w-3 h-3 text-[var(--primary)] inline ml-1" />
                )}
              </div>
            </PricingCard.PlanName>
            <PricingCard.Badge>
              {offer.is_featured ? 'Featured' : offer.is_popular ? 'Popular' : offer.category}
            </PricingCard.Badge>
          </PricingCard.Plan>
          
          <PricingCard.Price>
            <PricingCard.MainPrice className="text-[var(--success)]">{cashbackText}</PricingCard.MainPrice>
            <PricingCard.Period>Cashback</PricingCard.Period>
          </PricingCard.Price>

          {/* {!expired && (
            <div className="absolute top-3 right-3 flex items-center gap-1">
              <span className="w-2 h-2 bg-[var(--success)] rounded-full animate-pulse"></span>
              <span className="text-[0.6rem] font-bold text-[var(--success)]">LIVE</span>
            </div>
          )} */}
        </PricingCard.Header>
        </div>
        
        <PricingCard.Body>
          <PricingCard.List>
            {/* <PricingCard.ListItem className="text-[var(--foreground)] font-medium">
              <span className="mt-0.5">
                <CheckCircle2 className="h-4 w-4 text-[var(--success)]" />
              </span>
              <span className="line-clamp-2">{offer.title}</span>
            </PricingCard.ListItem> */}
            <PricingCard.ListItem>
              <span className="mt-0.5">
                <Users className="h-4 w-4 text-[var(--text-sub)]" />
              </span>
              <span className="text-[var(--text-sub)]">{formatNumber(offer.claimed_count)} claimed</span>
            </PricingCard.ListItem>
          </PricingCard.List>
        </PricingCard.Body>
      </PricingCard.Card>
      
      <style jsx>{`
        :global(.offer-card-link) {
          text-decoration: none;
          color: inherit;
        }
      `}</style>
    </Link>
  );
}
