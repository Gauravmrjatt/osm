'use client';

import { cn } from '@/lib/utils';

interface SkeletonProps extends React.HTMLAttributes<HTMLDivElement> {
  variant?: 'default' | 'circular' | 'card' | 'text' | 'banner';
}

export function Skeleton({ className, variant = 'default', ...props }: SkeletonProps) {
  return (
    <div
      className={cn(
        'animate-pulse bg-[var(--muted)]',
        variant === 'circular' && 'rounded-full',
        variant === 'card' && 'rounded-xl',
        variant === 'banner' && 'rounded-xl h-[150px]',
        variant === 'text' && 'rounded h-4',
        className
      )}
      {...props}
    />
  );
}

export function OfferCardSkeleton() {
  return (
    <div className="w-full border border-[var(--border-color)]/50 rounded-xl p-4 space-y-4">
      <div className="flex items-center gap-3">
        <Skeleton variant="circular" className="w-10 h-10" />
        <div className="space-y-2 flex-1">
          <Skeleton variant="text" className="w-24" />
          <Skeleton variant="text" className="w-16" />
        </div>
        <Skeleton variant="text" className="w-20 h-6" />
      </div>
      <div className="space-y-2">
        <Skeleton variant="text" className="w-full" />
        <Skeleton variant="text" className="w-3/4" />
      </div>
      <div className="flex justify-between">
        <Skeleton variant="text" className="w-24" />
        <Skeleton variant="text" className="w-16" />
      </div>
    </div>
  );
}

export function BannerSkeleton() {
  return (
    <div className="w-full">
      <Skeleton variant="banner" className="w-full" />
    </div>
  );
}

export function CategorySkeleton() {
  return (
    <div className="flex gap-2 overflow-x-auto">
      {[1, 2, 3, 4, 5].map((i) => (
        <Skeleton key={i} className="w-20 h-10 rounded-full flex-shrink-0" />
      ))}
    </div>
  );
}

export function FeaturedSkeleton() {
  return (
    <div className="space-y-3">
      <Skeleton variant="text" className="w-32 h-6" />
      <div className="flex gap-3 overflow-x-auto">
        {[1, 2, 3].map((i) => (
          <Skeleton key={i} variant="card" className="w-[160px] h-[180px] flex-shrink-0" />
        ))}
      </div>
    </div>
  );
}

export function StatsSkeleton() {
  return (
    <div className="grid grid-cols-3 gap-4">
      {[1, 2, 3].map((i) => (
        <div key={i} className="text-center space-y-2">
          <Skeleton variant="text" className="w-16 h-8 mx-auto" />
          <Skeleton variant="text" className="w-20 h-4 mx-auto" />
        </div>
      ))}
    </div>
  );
}

export function OfferDetailSkeleton() {
  return (
    <div className="max-w-[600px] mx-auto p-4 space-y-6">
      <div className="flex items-center gap-4">
        <Skeleton variant="circular" className="w-16 h-16" />
        <div className="space-y-2 flex-1">
          <Skeleton variant="text" className="w-32 h-6" />
          <Skeleton variant="text" className="w-24 h-4" />
        </div>
      </div>
      <Skeleton variant="card" className="w-full h-[200px]" />
      <div className="space-y-3">
        <Skeleton variant="text" className="w-full" />
        <Skeleton variant="text" className="w-full" />
        <Skeleton variant="text" className="w-3/4" />
      </div>
      <div className="grid grid-cols-2 gap-4">
        {[1, 2, 3, 4].map((i) => (
          <Skeleton key={i} variant="card" className="h-16" />
        ))}
      </div>
      <Skeleton variant="card" className="w-full h-14" />
    </div>
  );
}

export function AdminTableSkeleton({ rows = 5 }: { rows?: number }) {
  return (
    <div className="space-y-3">
      <div className="flex justify-between mb-4">
        <Skeleton variant="text" className="w-32 h-8" />
        <Skeleton variant="text" className="w-28 h-10" />
      </div>
      <Skeleton variant="card" className="w-full h-[400px]" />
    </div>
  );
}

export function SearchFilterSkeleton() {
  return (
    <div className="flex gap-2">
      <Skeleton variant="card" className="flex-1 h-14" />
      <Skeleton variant="card" className="w-24 h-14" />
    </div>
  );
}
