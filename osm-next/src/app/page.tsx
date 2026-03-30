'use client';

import { useState, useEffect } from 'react';
import { useSearchParams, useRouter } from 'next/navigation';
import Navbar from '@/components/Navbar';
import BannersSection from '@/components/BannersSection';
import FeaturedSection from '@/components/FeaturedSection';
import OfferCard from '@/components/OfferCard';
import StatsSection from '@/components/StatsSection';

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

export default function Home() {
  const searchParams = useSearchParams();
  const router = useRouter();
  const [categories, setCategories] = useState<Category[]>([]);
  const [offers, setOffers] = useState<Offer[]>([]);
  const [featuredOffers, setFeaturedOffers] = useState<Offer[]>([]);
  const [banners, setBanners] = useState<Banner[]>([]);
  const [stats, setStats] = useState<Stats>({ total_claimed: 0, active_offers: 0, max_cashback: 0 });
  const [loading, setLoading] = useState(true);

  const categoryFilter = searchParams.get('category') || 'All';
  const sortBy = searchParams.get('sort') || 'newest';

  useEffect(() => {
    async function fetchData() {
      try {
        const [catsRes, offersRes, featuredRes, bannersRes, statsRes] = await Promise.all([
          fetch('/api/categories'),
          fetch(`/api/offers?category=${encodeURIComponent(categoryFilter)}&sort=${sortBy}`),
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
  }, [categoryFilter, sortBy]);

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
      <div className="min-h-screen flex items-center justify-center">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[var(--primary)]"></div>
      </div>
    );
  }

  return (
    <div className="min-h-screen">
      <Navbar />
      
      <div className="max-w-[600px] mx-auto px-4 pb-[100px]">
        {/* Hero Banner */}
        <div className="bg-gradient-to-br from-[#1a1f2e] to-[#0d1117] rounded-[1.25rem] p-5 flex items-center gap-4 mb-5 border border-[var(--border)] relative overflow-hidden">
          <div className="absolute -right-[30px] -top-[30px] w-[150px] h-[150px] bg-radial-gradient from-[rgba(30,107,255,0.15)] to-transparent rounded-full"></div>
          <div className="w-[70px] h-[70px] bg-gradient-to-br from-[var(--primary)] to-[var(--primary-light)] rounded-2xl flex items-center justify-center text-3xl flex-shrink-0">
            $
          </div>
          <div>
            <div className="text-[0.65rem] text-[var(--text-sub)] uppercase tracking-wider mb-1">Welcome to</div>
            <div className="text-[1rem] font-bold mb-0.5">
              OS<span className="text-[var(--primary-light)]">M</span> Offers
            </div>
            <div className="text-[0.75rem] text-[var(--text-sub)]">Earn cashback on every order</div>
          </div>
        </div>

        {/* Banners */}
        <BannersSection banners={banners} />

        {/* Featured Offers */}
        <FeaturedSection offers={featuredOffers} />

        {/* Category Tabs */}
        <div className="flex gap-2 overflow-x-auto scrollbar-hide mb-5 pb-1">
          <button
            onClick={() => handleCategoryClick('All')}
            className={`flex-0-auto px-[18px] py-2.5 rounded-[var(--radius-pill)] text-[0.8rem] font-semibold whitespace-nowrap transition-all duration-200 border border-white/10 ${
              categoryFilter === 'All'
                ? 'bg-gradient-to-r from-[var(--primary)] to-[var(--primary-light)] text-white border-none shadow-[var(--glow)]'
                : 'bg-transparent text-[var(--text-sub)] hover:border-[var(--primary)] hover:text-[var(--text)]'
            }`}
          >
            All
          </button>
          {categories.map((cat) => (
            <button
              key={cat._id}
              onClick={() => handleCategoryClick(cat.name)}
              className={`flex-0-auto px-[18px] py-2.5 rounded-[var(--radius-pill)] text-[0.8rem] font-semibold whitespace-nowrap transition-all duration-200 border border-white/10 ${
                categoryFilter === cat.name
                  ? 'bg-gradient-to-r from-[var(--primary)] to-[var(--primary-light)] text-white border-none shadow-[var(--glow)]'
                  : 'bg-transparent text-[var(--text-sub)] hover:border-[var(--primary)] hover:text-[var(--text)]'
              }`}
            >
              {cat.emoji} {cat.name}
            </button>
          ))}
        </div>

        {/* Filter */}
        <div className="mb-4">
          <select
            value={sortBy}
            onChange={handleSortChange}
            className="border border-white/10 bg-[var(--bg-card)] text-[var(--text)] text-[0.75rem] font-medium px-3 py-2 rounded-[0.625rem] cursor-pointer outline-none appearance-none"
            style={{
              backgroundImage: `url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' fill='none'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%239AA4B2' stroke-width='2' stroke-linecap='round'/%3E%3C/svg%3E")`,
              backgroundRepeat: 'no-repeat',
              backgroundPosition: 'right 10px center',
              paddingRight: '28px'
            }}
          >
            <option value="newest">Newest</option>
            <option value="popular">Most Popular</option>
            <option value="expiry">Expiring Soon</option>
            <option value="cashback">Highest Cashback</option>
          </select>
        </div>

        {/* Offers List */}
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

        {/* Stats */}
        <StatsSection stats={stats} />
      </div>
    </div>
  );
}
