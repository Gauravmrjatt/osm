'use client';

import { useEffect, useRef } from 'react';

interface Banner {
  _id: string;
  image_url: string;
  link_url?: string;
}

export default function BannersSection({ banners }: { banners: Banner[] }) {
  const scrollRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    if (!banners || banners.length <= 1) return;
    
    const interval = setInterval(() => {
      if (scrollRef.current) {
        const scrollWidth = scrollRef.current.scrollWidth / banners.length;
        const currentScroll = scrollRef.current.scrollLeft;
        const nextScroll = currentScroll + scrollWidth;
        
        if (nextScroll >= scrollRef.current.scrollWidth) {
          scrollRef.current.scrollTo({ left: 0, behavior: 'smooth' });
        } else {
          scrollRef.current.scrollTo({ left: nextScroll, behavior: 'smooth' });
        }
      }
    }, 3000);

    return () => clearInterval(interval);
  }, [banners]);

  if (!banners || banners.length === 0) return null;

  return (
    <div className="mb-5">
      <div 
        ref={scrollRef}
        className="flex gap-3 overflow-x-auto scrollbar-hide pb-1"
        style={{ scrollSnapType: 'x mandatory' }}
      >
        {banners.map((banner) => (
          <a
            key={banner._id}
            href={banner.link_url || '#'}
            style={{ 
              flex: '0 0 calc(100vw - 32px)', 
              maxWidth: '400px',
              scrollSnapAlign: 'start',
              display: 'block'
            }}
          >
            <img 
              src={`/uploads/${banner.image_url}`}
              alt="Banner"
              className="w-full h-[100px] rounded-[0.875rem] object-cover"
            />
          </a>
        ))}
      </div>
    </div>
  );
}
