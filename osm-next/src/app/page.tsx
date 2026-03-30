'use client';

import { Suspense } from 'react';
import dynamic from 'next/dynamic';
import Navbar from '@/components/Navbar';
import { BannerSkeleton, SearchFilterSkeleton, FeaturedSkeleton, CategorySkeleton, OfferCardSkeleton, StatsSkeleton } from '@/components/ui/skeleton';

const HomeContent = dynamic(() => import('@/components/HomeContent'), {
  ssr: false,
  loading: () => (
    <div className="max-w-[600px] mx-auto px-4 pb-[100px] relative z-10 space-y-6 mt-5">
      <BannerSkeleton />
      <SearchFilterSkeleton />
      <FeaturedSkeleton />
      <CategorySkeleton />
      <div className="flex flex-col gap-3">
        {[1, 2, 3, 4, 5].map((i) => (
          <OfferCardSkeleton key={i} />
        ))}
      </div>
      <StatsSkeleton />
    </div>
  ),
});

export default function Home() {
  return (
    <div className="min-h-screen relative">
      <div className="fixed inset-0 dotted-grid dotted-grid-mask pointer-events-none z-0" />
      <div className="fixed -top-1/2 left-1/2 -translate-x-1/2 w-[120vmin] h-[120vmin] rounded-full radial-spotlight-blue blur-[50px] pointer-events-none z-0" />
      
      <Navbar />
      
      <Suspense fallback={
        <div className="max-w-[600px] mx-auto px-4 pb-[100px] relative z-10 space-y-6 mt-5">
          <BannerSkeleton />
          <SearchFilterSkeleton />
          <FeaturedSkeleton />
          <CategorySkeleton />
          <div className="flex flex-col gap-3">
            {[1, 2, 3, 4, 5].map((i) => (
              <OfferCardSkeleton key={i} />
            ))}
          </div>
          <StatsSkeleton />
        </div>
      }>
        <HomeContent />
      </Suspense>
    </div>
  );
}
