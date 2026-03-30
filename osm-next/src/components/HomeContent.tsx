'use client';

import { useState, useEffect } from 'react';
import { useSearchParams, useRouter } from 'next/navigation';
import BannersSection from '@/components/BannersSection';
import FeaturedSection from '@/components/FeaturedSection';
import OfferCard from '@/components/OfferCard';
import StatsSection from '@/components/StatsSection';
import AnimatedSearchBar from '@/components/ui/animated-glowing-search-bar';
import { OfferCardSkeleton, CategorySkeleton, FeaturedSkeleton, StatsSkeleton, SearchFilterSkeleton, BannerSkeleton } from '@/components/ui/skeleton';

interface Category {
  _id: string;
  name: string;
  emoji: string;
}

interface Offer {
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
}

interface Banner {
  _id: string;
  image_url: string;
  link_url?: string;
}

interface Stats {
  total_claimed: number;
  active_offers: number;
  max_cashback: number;
}

export default function HomeContent() {
  const searchParams = useSearchParams();
  const router = useRouter();
  const [categories, setCategories] = useState<Category[]>([]);
  const [offers, setOffers] = useState<Offer[]>([]);
  const [featuredOffers, setFeaturedOffers] = useState<Offer[]>([]);
  const [banners, setBanners] = useState<Banner[]>([]);
  const [stats, setStats] = useState<Stats>({ total_claimed: 0, active_offers: 0, max_cashback: 0 });
  const [loading, setLoading] = useState(true);
  const [searchQuery, setSearchQuery] = useState('');

  const categoryFilter = searchParams.get('category') || 'All';
  const sortBy = searchParams.get('sort') || 'newest';

  useEffect(() => {
    async function fetchData() {
      try {
        const searchParam = searchQuery ? `&search=${encodeURIComponent(searchQuery)}` : '';
        const [catsRes, offersRes, featuredRes, bannersRes, statsRes] = await Promise.all([
          fetch('/api/categories'),
          fetch(`/api/offers?category=${encodeURIComponent(categoryFilter)}&sort=${sortBy}${searchParam}`),
          fetch('/api/featured'),
          fetch('/api/banners'),
          fetch('/api/stats'),
        ]);

        const catsData = await catsRes.json();
        const offersData = await offersRes.json();
        const featuredData = await featuredRes.json();
        const bannersData = await bannersRes.json();
        const statsData = await statsRes.json();

        setCategories(catsData);
        setOffers(Array.isArray(offersData) ? offersData : []);
        setFeaturedOffers(Array.isArray(featuredData) ? featuredData : []);
        setBanners(Array.isArray(bannersData) ? bannersData : []);
        setStats(statsData);
      } catch (error) {
        console.error('Error fetching data:', error);
      } finally {
        setLoading(false);
      }
    }

    fetchData();
  }, [categoryFilter, sortBy, searchQuery]);

  const handleSearch = (query: string) => {
    setSearchQuery(query);
  };

  const clearSearch = () => {
    setSearchQuery('');
  };

  const handleCategoryClick = (catName: string) => {
    const params = new URLSearchParams(searchParams.toString());
    if (catName === 'All') {
      params.delete('category');
    } else {
      params.set('category', catName);
    }
    router.push(`/?${params.toString()}`);
  };

  const handleSortChange = (e: React.ChangeEvent<HTMLSelectElement>) => {
    const params = new URLSearchParams(searchParams.toString());
    params.set('sort', e.target.value);
    router.push(`/?${params.toString()}`);
  };

  if (loading) {
    return (
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
    );
  }

  return (
    <div className="max-w-[600px] mx-auto px-4 pb-[100px] relative z-10">
      <div className='mt-5'>
        <BannersSection banners={banners} />
      </div>

      <div className="mt-5 mb-4 relative group">
        <div className="absolute -inset-0.5 rounded-xl bg-gradient-to-r from-[#402fb5] via-[#cf30aa] to-[#402fb5] opacity-60 group-hover:opacity-100 transition-opacity duration-300"></div>
        
        <div className="relative flex gap-2 items-center bg-[var(--bg-card)] rounded-xl p-1">
          <div className="flex-1">
            <AnimatedSearchBar 
              onSearch={handleSearch}
              placeholder="Search offers, brands..."
            />
          </div>
          <select
            value={sortBy}
            onChange={handleSortChange}
            className="h-[54px] min-w-[100px] border-0 bg-transparent text-[var(--foreground)] text-[0.75rem] font-medium px-3 cursor-pointer outline-none appearance-none"
            style={{
              backgroundImage: `url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' fill='none'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%239AA4B2' stroke-width='2' stroke-linecap='round'/%3E%3C/svg%3E")`,
              backgroundRepeat: 'no-repeat',
              backgroundPosition: 'right 10px center',
              paddingRight: '28px'
            }}
          >
            <option value="newest">Newest</option>
            <option value="popular">Popular</option>
            <option value="expiry">Expiring</option>
            <option value="cashback">Cashback</option>
          </select>
        </div>
        {searchQuery && (
          <div className="flex items-center justify-center mt-3">
            <button 
              onClick={clearSearch}
              className="text-sm text-[var(--text-sub)] hover:text-[var(--primary)] flex items-center gap-1"
            >
              Clear search "{searchQuery}"
            </button>
          </div>
        )}
      </div>

      <FeaturedSection offers={featuredOffers} />

      <div className="flex gap-2 overflow-x-auto scrollbar-hide mb-5 pb-1">
        <button
          onClick={() => handleCategoryClick('All')}
          className={`flex-0-auto px-[18px] py-2.5 rounded-[var(--radius-pill)] text-[0.8rem] font-semibold whitespace-nowrap transition-all duration-200 border ${categoryFilter === 'All'
              ? 'bg-gradient-to-r from-[var(--primary)] to-[var(--primary-light)] text-white border-none shadow-[var(--glow)]'
              : 'bg-transparent text-[var(--text-sub)] border-[var(--border-color)] hover:border-[var(--primary)] hover:text-[var(--text)]'
            }`}
        >
          All
        </button>
        {categories.map((cat) => (
          <button
            key={cat._id}
            onClick={() => handleCategoryClick(cat.name)}
            className={`flex-0-auto px-[18px] py-2.5 rounded-[var(--radius-pill)] text-[0.8rem] font-semibold whitespace-nowrap transition-all duration-200 border ${categoryFilter === cat.name
                ? 'bg-gradient-to-r from-[var(--primary)] to-[var(--primary-light)] text-white border-none shadow-[var(--glow)]'
                : 'bg-transparent text-[var(--text-sub)] border-[var(--border-color)] hover:border-[var(--primary)] hover:text-[var(--text)]'
              }`}
          >
            {cat.emoji} {cat.name}
          </button>
        ))}
      </div>

      <div className="flex flex-col gap-3 mb-6">
        {offers.length > 0 ? (
          offers.map((offer, index) => (
            <OfferCard key={offer._id} offer={offer} index={index} />
          ))
        ) : (
          <div className="text-center py-10 text-[var(--text-sub)]">
            <p className="text-[0.85rem] mb-3">No offers found matching your criteria.</p>
            <a href="/" className="text-[var(--primary-light)] text-[0.8rem] font-semibold">Clear filters</a>
          </div>
        )}
      </div>

      <StatsSection stats={stats} />
    </div>
  );
}
