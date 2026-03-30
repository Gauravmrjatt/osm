'use client';

import { useEffect, useRef, useState } from 'react';

interface Banner {
  _id: string;
  image_url: string;
  link_url?: string;
}

export default function BannersSection({ banners }: { banners: Banner[] }) {
  const scrollRef = useRef<HTMLDivElement>(null);
  const [currentIndex, setCurrentIndex] = useState(0);

  const scrollToNext = () => {
    if (scrollRef.current && banners.length > 0) {
      const nextIndex = (currentIndex + 1) % banners.length;
      const scrollAmount = scrollRef.current.scrollWidth / (banners.length * 2);
      scrollRef.current.scrollTo({ 
        left: scrollAmount * nextIndex, 
        behavior: 'smooth' 
      });
      setCurrentIndex(nextIndex);
    }
  };

  useEffect(() => {
    if (!banners || banners.length === 0) return;
    
    const interval = setInterval(scrollToNext, 3000);
    return () => clearInterval(interval);
  }, [currentIndex, banners]);

  const handleScroll = () => {
    if (scrollRef.current && banners.length > 0) {
      const scrollWidth = scrollRef.current.scrollWidth / 2;
      const scrollLeft = scrollRef.current.scrollLeft;
      
      if (scrollLeft >= scrollWidth) {
        scrollRef.current.scrollLeft = scrollLeft - scrollWidth;
      }
    }
  };

  if (!banners || banners.length === 0) return null;

  const duplicatedBanners = [...banners, ...banners];

  return (
    <div className="mb-5">
      <div 
        ref={scrollRef}
        className="flex gap-3 overflow-x-auto scrollbar-hide pb-1"
        style={{ scrollSnapType: 'x mandatory' }}
        onScroll={handleScroll}
      >
        {duplicatedBanners.map((banner, index) => (
          <a
            key={`${banner._id}-${index}`}
            href={banner.link_url || '#'}
            style={{ 
              flex: '0 0 calc(100vw - 32px)', 
              maxWidth: '400px',
              scrollSnapAlign: 'start',
              display: 'block'
            }}
          >
            <img 
              src={`/api/files/${banner.image_url}`}
              alt="Banner"
              className="w-full h-[100px] rounded-[0.875rem] object-cover"
            />
          </a>
        ))}
      </div>
      
      {banners.length > 1 && (
        <div className="flex justify-center gap-1.5 mt-3">
          {banners.map((_, index) => (
            <button
              key={index}
              onClick={() => {
                setCurrentIndex(index);
                if (scrollRef.current) {
                  const scrollAmount = scrollRef.current.scrollWidth / (banners.length * 2);
                  scrollRef.current.scrollTo({ 
                    left: scrollAmount * index, 
                    behavior: 'smooth' 
                  });
                }
              }}
              className={`w-1.5 h-1.5 rounded-full transition-all ${
                index === currentIndex 
                  ? 'bg-[var(--primary)] w-4' 
                  : 'bg-[var(--muted-foreground)]'
              }`}
            />
          ))}
        </div>
      )}
    </div>
  );
}
