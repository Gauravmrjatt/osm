'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';

const ADMIN_PASSWORD = 'Abduu@heh6262';

interface Offer {
  _id: string;
  title: string;
  brand_name: string;
  brand_emoji: string;
  category: string;
  max_cashback: number;
  cashback_rate: number;
  cashback_type: string;
  expiry_date: string;
  status: string;
  is_featured: boolean;
}

interface Category {
  _id: string;
  name: string;
  emoji: string;
  sort_order: number;
}

interface Banner {
  _id: string;
  image_url: string;
  link_url: string;
  sort_order: number;
  status: string;
}

export default function AdminPage() {
  const router = useRouter();
  const [isLoggedIn, setIsLoggedIn] = useState(false);
  const [password, setPassword] = useState('');
  const [activeTab, setActiveTab] = useState('offers');
  const [offers, setOffers] = useState<Offer[]>([]);
  const [categories, setCategories] = useState<Category[]>([]);
  const [banners, setBanners] = useState<Banner[]>([]);
  const [message, setMessage] = useState({ text: '', type: '' });
  const [editingOffer, setEditingOffer] = useState<Offer | null>(null);
  const [editingCategory, setEditingCategory] = useState<Category | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const loggedIn = localStorage.getItem('admin_logged_in');
    if (loggedIn === 'true') {
      setIsLoggedIn(true);
      fetchData();
    } else {
      setLoading(false);
    }
  }, []);

  const fetchData = async () => {
    try {
      const [offersRes, catsRes, bannersRes] = await Promise.all([
        fetch('/api/all-offers'),
        fetch('/api/categories'),
        fetch('/api/banners'),
      ]);
      setOffers(await offersRes.json());
      setCategories(await catsRes.json());
      setBanners(await bannersRes.json());
    } catch (error) {
      console.error('Error fetching data:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleLogin = (e: React.FormEvent) => {
    e.preventDefault();
    if (password === ADMIN_PASSWORD) {
      setIsLoggedIn(true);
      localStorage.setItem('admin_logged_in', 'true');
      fetchData();
    } else {
      setMessage({ text: 'Incorrect password', type: 'error' });
    }
  };

  const handleLogout = () => {
    setIsLoggedIn(false);
    localStorage.removeItem('admin_logged_in');
  };

  const deleteOffer = async (id: string) => {
    if (!confirm('Delete this offer?')) return;
    try {
      const res = await fetch(`/api/offer/${id}`, { method: 'DELETE' });
      if (res.ok) {
        setOffers(offers.filter(o => o._id !== id));
        setMessage({ text: 'Offer deleted', type: 'success' });
      }
    } catch (error) {
      console.error('Error:', error);
    }
  };

  const deleteCategory = async (id: string) => {
    if (!confirm('Delete this category?')) return;
    try {
      const res = await fetch(`/api/categories?id=${id}`, { method: 'DELETE' });
      if (res.ok) {
        setCategories(categories.filter(c => c._id !== id));
        setMessage({ text: 'Category deleted', type: 'success' });
      }
    } catch (error) {
      console.error('Error:', error);
    }
  };

  const deleteBanner = async (id: string) => {
    if (!confirm('Delete this banner?')) return;
    try {
      const res = await fetch(`/api/banners?id=${id}`, { method: 'DELETE' });
      if (res.ok) {
        setBanners(banners.filter(b => b._id !== id));
        setMessage({ text: 'Banner deleted', type: 'success' });
      }
    } catch (error) {
      console.error('Error:', error);
    }
  };

  const toggleBannerStatus = async (banner: Banner) => {
    const newStatus = banner.status === 'active' ? 'inactive' : 'active';
    try {
      const res = await fetch('/api/banners', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ _id: banner._id, status: newStatus }),
      });
      if (res.ok) {
        setBanners(banners.map(b => b._id === banner._id ? { ...b, status: newStatus } : b));
      }
    } catch (error) {
      console.error('Error:', error);
    }
  };

  const handleSaveOffer = async (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    const formData = new FormData(e.currentTarget);
    const data = Object.fromEntries(formData);
    
    const offerData = {
      ...data,
      max_cashback: Number(data.max_cashback),
      cashback_rate: Number(data.cashback_rate),
      min_order_amount: Number(data.min_order_amount),
      claimed_count: Number(data.claimed_count),
      rating: Number(data.rating),
      is_featured: data.is_featured === 'on',
      is_verified: data.is_verified === 'on',
      is_popular: data.is_popular === 'on',
      cashback_type: data.cashback_type || 'flat',
      payout_type: data.payout_type || 'instant',
      status: data.status || 'active',
    };

    try {
      const url = editingOffer?._id ? `/api/offer/${editingOffer._id}` : '/api/offers';
      const method = editingOffer?._id ? 'PUT' : 'POST';
      
      const res = await fetch(url, {
        method,
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(offerData),
      });
      
      if (res.ok) {
        setMessage({ text: 'Offer saved', type: 'success' });
        setEditingOffer(null);
        fetchData();
      }
    } catch (error) {
      console.error('Error:', error);
    }
  };

  const handleSaveCategory = async (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    const formData = new FormData(e.currentTarget);
    const data = Object.fromEntries(formData);
    
    const catData = {
      ...data,
      sort_order: Number(data.sort_order),
    };

    try {
      const url = editingCategory?._id ? '/api/categories' : '/api/categories';
      const method = editingCategory?._id ? 'PUT' : 'POST';
      
      const res = await fetch(url, {
        method,
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ _id: editingCategory?._id, ...catData }),
      });
      
      if (res.ok) {
        setMessage({ text: 'Category saved', type: 'success' });
        setEditingCategory(null);
        fetchData();
      }
    } catch (error) {
      console.error('Error:', error);
    }
  };

  const handleBannerUpload = async (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    const formData = new FormData(e.currentTarget);
    const file = formData.get('banner_image') as File;
    
    if (!file || !file.name) {
      setMessage({ text: 'Please select a file', type: 'error' });
      return;
    }

    try {
      const uploadRes = await fetch('/api/upload', {
        method: 'POST',
        body: formData,
      });
      
      const uploadData = await uploadRes.json();
      
      if (uploadData.success) {
        const bannerRes = await fetch('/api/banners', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            image_url: uploadData.filename,
            link_url: formData.get('banner_link'),
            sort_order: Number(formData.get('banner_order')) || banners.length + 1,
            status: 'active',
          }),
        });
        
        if (bannerRes.ok) {
          setMessage({ text: 'Banner uploaded', type: 'success' });
          fetchData();
        }
      }
    } catch (error) {
      console.error('Error:', error);
    }
  };

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[var(--primary)]"></div>
      </div>
    );
  }

  if (!isLoggedIn) {
    return (
      <div className="min-h-screen flex items-center justify-center p-4">
        <div className="bg-white rounded-[var(--radius)] p-10 max-w-[400px] w-full text-center shadow-lg">
          <h2 className="font-extrabold text-[1.4rem] text-[var(--primary)] mb-2">
            OS<span className="text-[var(--primary-light)]">M</span> Admin
          </h2>
          <p className="text-[var(--text-sub)] mb-6">Enter password to access admin panel</p>
          {message.text && (
            <div className={`p-3 rounded-lg mb-4 text-sm font-semibold ${message.type === 'error' ? 'bg-red-100 text-red-600' : 'bg-green-100 text-green-600'}`}>
              {message.text}
            </div>
          )}
          <form onSubmit={handleLogin}>
            <input
              type="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              placeholder="Enter password"
              className="w-full p-3.5 border border-gray-200 rounded-xl text-center text-lg mb-4"
              required
            />
            <button type="submit" className="w-full py-3 bg-[var(--primary)] text-white font-bold rounded-xl">
              Login
            </button>
          </form>
          <Link href="/" className="block mt-4 text-[var(--primary)] text-sm">← Back to Home</Link>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-[#f5f6fa] text-[#1e1b4b]">
      {/* Navbar */}
      <nav className="bg-white rounded-[var(--radius)] px-6 py-4 flex items-center justify-between mb-6 shadow-sm mx-4 mt-4">
        <div className="font-extrabold text-[1.4rem] text-[var(--primary)]">
          OS<span className="text-[var(--primary-light)]">M</span> Admin
        </div>
        <div className="flex items-center gap-4">
          <Link href="/" className="text-[var(--text-sub)] font-semibold text-sm hover:text-[var(--primary)]">View Site</Link>
          <button onClick={handleLogout} className="px-4 py-2 bg-red-100 text-red-600 font-semibold rounded-lg text-sm">Logout</button>
        </div>
      </nav>

      {message.text && (
        <div className={`mx-4 p-3 rounded-lg mb-4 text-sm font-semibold max-w-[1200px] ${message.type === 'error' ? 'bg-red-100 text-red-600' : 'bg-green-100 text-green-600'}`}>
          {message.text}
        </div>
      )}

      {/* Tabs */}
      <div className="flex gap-2 mx-4 mb-6 overflow-x-auto">
        {['offers', 'categories', 'banners'].map(tab => (
          <button
            key={tab}
            onClick={() => setActiveTab(tab)}
            className={`px-5 py-2.5 rounded-[10px] font-semibold text-sm whitespace-nowrap ${
              activeTab === tab ? 'bg-[var(--primary)] text-white' : 'bg-white text-[var(--text-sub)] shadow-sm'
            }`}
          >
            {tab.charAt(0).toUpperCase() + tab.slice(1)}
          </button>
        ))}
      </div>

      <div className="px-4 pb-8 max-w-[1200px] mx-auto">
        {/* Offers Tab */}
        {activeTab === 'offers' && (
          <div className="bg-white rounded-[var(--radius)] p-6 shadow-sm">
            <div className="flex justify-between items-center mb-6">
              <h2 className="font-extrabold text-[1.1rem]">All Offers</h2>
              <button onClick={() => setEditingOffer({} as Offer)} className="px-4 py-2 bg-[var(--primary)] text-white font-bold rounded-lg text-sm">
                + Add New Offer
              </button>
            </div>

            {/* Edit/Add Offer Form */}
            {editingOffer !== null && (
              <form onSubmit={handleSaveOffer} className="bg-[#f5f6fa] p-5 rounded-xl mb-6">
                <h3 className="font-bold text-sm mb-4">{editingOffer._id ? 'Edit Offer' : 'Add New Offer'}</h3>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <input name="title" defaultValue={editingOffer.title} placeholder="Title *" className="p-2.5 border border-gray-200 rounded-lg text-sm" required />
                  <input name="brand_name" defaultValue={editingOffer.brand_name} placeholder="Brand Name *" className="p-2.5 border border-gray-200 rounded-lg text-sm" required />
                  <input name="brand_emoji" defaultValue={editingOffer.brand_emoji || '🎁'} placeholder="Brand Emoji" className="p-2.5 border border-gray-200 rounded-lg text-sm" />
                  <input name="logo_image" defaultValue={editingOffer.logo_image} placeholder="Logo Image Filename" className="p-2.5 border border-gray-200 rounded-lg text-sm" />
                  <select name="category" defaultValue={editingOffer.category || 'General'} className="p-2.5 border border-gray-200 rounded-lg text-sm">
                    {categories.map(c => <option key={c._id} value={c.name}>{c.emoji} {c.name}</option>)}
                  </select>
                  <input name="description" defaultValue={editingOffer.description} placeholder="Description" className="p-2.5 border border-gray-200 rounded-lg text-sm" />
                  <input name="min_order_amount" type="number" defaultValue={editingOffer.min_order_amount || 0} placeholder="Min Order Amount" className="p-2.5 border border-gray-200 rounded-lg text-sm" />
                  <input name="max_cashback" type="number" defaultValue={editingOffer.max_cashback || 0} placeholder="Max Cashback" className="p-2.5 border border-gray-200 rounded-lg text-sm" />
                  <input name="cashback_rate" type="number" defaultValue={editingOffer.cashback_rate || 0} placeholder="Cashback Rate %" className="p-2.5 border border-gray-200 rounded-lg text-sm" />
                  <select name="cashback_type" defaultValue={editingOffer.cashback_type || 'flat'} className="p-2.5 border border-gray-200 rounded-lg text-sm">
                    <option value="flat">Flat Amount</option>
                    <option value="percentage">Percentage</option>
                  </select>
                  <input name="expiry_date" type="date" defaultValue={editingOffer.expiry_date?.split('T')[0]} className="p-2.5 border border-gray-200 rounded-lg text-sm" />
                  <input name="promo_code" defaultValue={editingOffer.promo_code} placeholder="Promo Code" className="p-2.5 border border-gray-200 rounded-lg text-sm" />
                  <input name="redirect_url" defaultValue={editingOffer.redirect_url} placeholder="Redirect URL" className="p-2.5 border border-gray-200 rounded-lg text-sm" />
                  <input name="link2" defaultValue={editingOffer.link2} placeholder="Link 2 (Optional)" className="p-2.5 border border-gray-200 rounded-lg text-sm" />
                  <input name="claimed_count" type="number" defaultValue={editingOffer.claimed_count || 0} placeholder="Claimed Count" className="p-2.5 border border-gray-200 rounded-lg text-sm" />
                  <input name="rating" type="number" step="0.1" defaultValue={editingOffer.rating || 0} placeholder="Rating" className="p-2.5 border border-gray-200 rounded-lg text-sm" />
                  <select name="status" defaultValue={editingOffer.status || 'active'} className="p-2.5 border border-gray-200 rounded-lg text-sm">
                    <option value="active">Active</option>
                    <option value="expired">Expired</option>
                    <option value="draft">Draft</option>
                  </select>
                  <select name="payout_type" defaultValue={editingOffer.payout_type || 'instant'} className="p-2.5 border border-gray-200 rounded-lg text-sm">
                    <option value="instant">Instant</option>
                    <option value="24-72h">24-72 Hours</option>
                  </select>
                </div>
                <div className="flex gap-4 mt-4">
                  <label className="flex items-center gap-2 text-sm font-medium">
                    <input type="checkbox" name="is_featured" defaultChecked={editingOffer.is_featured} /> Featured
                  </label>
                  <label className="flex items-center gap-2 text-sm font-medium">
                    <input type="checkbox" name="is_verified" defaultChecked={editingOffer.is_verified} /> Verified
                  </label>
                  <label className="flex items-center gap-2 text-sm font-medium">
                    <input type="checkbox" name="is_popular" defaultChecked={editingOffer.is_popular} /> Popular
                  </label>
                </div>
                <div className="flex gap-2 mt-4">
                  <button type="submit" className="px-6 py-2.5 bg-[var(--primary)] text-white font-bold rounded-lg text-sm">Save</button>
                  <button type="button" onClick={() => setEditingOffer(null)} className="px-6 py-2.5 bg-gray-200 text-gray-700 font-bold rounded-lg text-sm">Cancel</button>
                </div>
              </form>
            )}

            <div className="overflow-x-auto">
              <table className="w-full">
                <thead>
                  <tr className="border-b">
                    <th className="text-left py-3 px-3 text-xs font-bold text-[var(--text-sub)] uppercase">ID</th>
                    <th className="text-left py-3 px-3 text-xs font-bold text-[var(--text-sub)] uppercase">Title</th>
                    <th className="text-left py-3 px-3 text-xs font-bold text-[var(--text-sub)] uppercase">Brand</th>
                    <th className="text-left py-3 px-3 text-xs font-bold text-[var(--text-sub)] uppercase">Category</th>
                    <th className="text-left py-3 px-3 text-xs font-bold text-[var(--text-sub)] uppercase">Cashback</th>
                    <th className="text-left py-3 px-3 text-xs font-bold text-[var(--text-sub)] uppercase">Expires</th>
                    <th className="text-left py-3 px-3 text-xs font-bold text-[var(--text-sub)] uppercase">Status</th>
                    <th className="text-left py-3 px-3 text-xs font-bold text-[var(--text-sub)] uppercase">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {offers.map(offer => (
                    <tr key={offer._id} className="border-b hover:bg-gray-50">
                      <td className="py-3 px-3 text-sm">{offer._id.slice(-6)}</td>
                      <td className="py-3 px-3 text-sm max-w-[200px] truncate">{offer.title}</td>
                      <td className="py-3 px-3 text-sm">{offer.brand_emoji} {offer.brand_name}</td>
                      <td className="py-3 px-3 text-sm">{offer.category}</td>
                      <td className="py-3 px-3 text-sm font-bold">{offer.cashback_type === 'flat' ? `₹${offer.max_cashback}` : `${offer.cashback_rate}%`}</td>
                      <td className="py-3 px-3 text-sm">{new Date(offer.expiry_date).toLocaleDateString('en-GB')}</td>
                      <td className="py-3 px-3">
                        <span className={`px-2.5 py-1 rounded-[20px] text-[0.7rem] font-bold ${
                          offer.status === 'active' ? 'bg-green-100 text-green-600' : 
                          offer.status === 'expired' ? 'bg-red-100 text-red-600' : 'bg-yellow-100 text-yellow-600'
                        }`}>
                          {offer.status}
                        </span>
                      </td>
                      <td className="py-3 px-3">
                        <div className="flex gap-2">
                          <button onClick={() => setEditingOffer(offer)} className="px-3 py-1 bg-gray-100 text-gray-700 rounded-md text-xs font-medium">Edit</button>
                          <button onClick={() => deleteOffer(offer._id)} className="px-3 py-1 bg-red-100 text-red-600 rounded-md text-xs font-medium">Delete</button>
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        )}

        {/* Categories Tab */}
        {activeTab === 'categories' && (
          <div className="bg-white rounded-[var(--radius)] p-6 shadow-sm">
            <div className="flex justify-between items-center mb-6">
              <h2 className="font-extrabold text-[1.1rem]">Manage Categories</h2>
              <button onClick={() => setEditingCategory({} as Category)} className="px-4 py-2 bg-[var(--primary)] text-white font-bold rounded-lg text-sm">
                + Add Category
              </button>
            </div>

            {editingCategory !== null && (
              <form onSubmit={handleSaveCategory} className="bg-[#f5f6fa] p-5 rounded-xl mb-6">
                <h3 className="font-bold text-sm mb-4">{editingCategory._id ? 'Edit Category' : 'Add Category'}</h3>
                <div className="grid grid-cols-3 gap-4">
                  <input name="name" defaultValue={editingCategory.name} placeholder="Category Name" className="p-2.5 border border-gray-200 rounded-lg text-sm" required />
                  <input name="emoji" defaultValue={editingCategory.emoji || '📌'} placeholder="Emoji" className="p-2.5 border border-gray-200 rounded-lg text-sm" />
                  <input name="sort_order" type="number" defaultValue={editingCategory.sort_order || categories.length + 1} placeholder="Sort Order" className="p-2.5 border border-gray-200 rounded-lg text-sm" />
                </div>
                <div className="flex gap-2 mt-4">
                  <button type="submit" className="px-6 py-2.5 bg-[var(--primary)] text-white font-bold rounded-lg text-sm">Save</button>
                  <button type="button" onClick={() => setEditingCategory(null)} className="px-6 py-2.5 bg-gray-200 text-gray-700 font-bold rounded-lg text-sm">Cancel</button>
                </div>
              </form>
            )}

            <table className="w-full">
              <thead>
                <tr className="border-b">
                  <th className="text-left py-3 px-3 text-xs font-bold text-[var(--text-sub)] uppercase">Order</th>
                  <th className="text-left py-3 px-3 text-xs font-bold text-[var(--text-sub)] uppercase">Emoji</th>
                  <th className="text-left py-3 px-3 text-xs font-bold text-[var(--text-sub)] uppercase">Name</th>
                  <th className="text-left py-3 px-3 text-xs font-bold text-[var(--text-sub)] uppercase">Actions</th>
                </tr>
              </thead>
              <tbody>
                {categories.map(cat => (
                  <tr key={cat._id} className="border-b hover:bg-gray-50">
                    <td className="py-3 px-3 text-sm">{cat.sort_order}</td>
                    <td className="py-3 px-3 text-xl">{cat.emoji}</td>
                    <td className="py-3 px-3 text-sm font-semibold">{cat.name}</td>
                    <td className="py-3 px-3">
                      <div className="flex gap-2">
                        <button onClick={() => setEditingCategory(cat)} className="px-3 py-1 bg-gray-100 text-gray-700 rounded-md text-xs font-medium">Edit</button>
                        <button onClick={() => deleteCategory(cat._id)} className="px-3 py-1 bg-red-100 text-red-600 rounded-md text-xs font-medium">Delete</button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}

        {/* Banners Tab */}
        {activeTab === 'banners' && (
          <div className="bg-white rounded-[var(--radius)] p-6 shadow-sm">
            <div className="flex justify-between items-center mb-6">
              <h2 className="font-extrabold text-[1.1rem]">Manage Banners</h2>
            </div>

            <form onSubmit={handleBannerUpload} className="bg-[#f5f6fa] p-5 rounded-xl mb-6">
              <div className="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div>
                  <label className="block text-xs font-semibold text-[var(--text-sub)] mb-1">Upload Banner</label>
                  <input type="file" name="banner_image" accept="image/*" className="p-2.5 border border-gray-200 rounded-lg text-sm w-full" required />
                </div>
                <div>
                  <label className="block text-xs font-semibold text-[var(--text-sub)] mb-1">Link URL</label>
                  <input type="text" name="banner_link" placeholder="https://example.com" className="p-2.5 border border-gray-200 rounded-lg text-sm w-full" />
                </div>
                <div>
                  <label className="block text-xs font-semibold text-[var(--text-sub)] mb-1">Order</label>
                  <input type="number" name="banner_order" defaultValue={banners.length + 1} className="p-2.5 border border-gray-200 rounded-lg text-sm w-full" />
                </div>
                <button type="submit" className="px-6 py-2.5 bg-[var(--primary)] text-white font-bold rounded-lg text-sm">Upload</button>
              </div>
            </form>

            <div className="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
              {banners.map(banner => (
                <div key={banner._id} className="border border-gray-200 rounded-xl overflow-hidden">
                  <img src={`/uploads/${banner.image_url}`} alt="Banner" className="w-full h-[120px] object-cover" />
                  <div className="p-3">
                    <div className="flex justify-between items-center mb-2">
                      <span className={`px-2 py-1 rounded-[20px] text-[0.7rem] font-bold ${banner.status === 'active' ? 'bg-green-100 text-green-600' : 'bg-gray-100 text-gray-600'}`}>
                        {banner.status}
                      </span>
                      <span className="text-xs text-[var(--text-sub)]">Order: {banner.sort_order}</span>
                    </div>
                    <div className="flex gap-2">
                      <button onClick={() => toggleBannerStatus(banner)} className="flex-1 px-3 py-1 bg-gray-100 text-gray-700 rounded-md text-xs font-medium">
                        {banner.status === 'active' ? 'Disable' : 'Enable'}
                      </button>
                      <button onClick={() => deleteBanner(banner._id)} className="flex-1 px-3 py-1 bg-red-100 text-red-600 rounded-md text-xs font-medium">Delete</button>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
