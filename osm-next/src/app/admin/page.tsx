'use client';

import { useState, useEffect } from 'react';
import Link from 'next/link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { ThemeToggle } from '@/components/theme-toggle';
import { FileUpload } from '@/components/ui/file-upload';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/components/ui/tabs';
import { Skeleton, AdminTableSkeleton } from '@/components/ui/skeleton';
import { 
  Tag, 
  FolderCog, 
  Image as ImageIcon, 
  Plus, 
  Edit, 
  Trash2, 
  Upload, 
  Eye,
  CheckCircle,
  XCircle,
  Clock
} from 'lucide-react';

const ADMIN_PASSWORD = 'Abduu@heh6262';

interface OfferStep {
  step_number: number;
  step_title: string;
  step_description?: string;
  step_time?: string;
}

interface Offer {
  _id: string;
  title: string;
  description?: string;
  brand_name: string;
  brand_emoji: string;
  category: string;
  logo_image: string;
  video_file: string;
  max_cashback: number;
  cashback_rate: number;
  cashback_type: string;
  min_order_amount?: number;
  expiry_date: string;
  promo_code?: string;
  redirect_url?: string;
  link2?: string;
  claimed_count?: number;
  rating?: number;
  status: string;
  is_featured: boolean;
  is_verified?: boolean;
  is_popular?: boolean;
  payout_type?: string;
  steps?: OfferStep[];
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
  const [isLoggedIn, setIsLoggedIn] = useState(false);
  const [password, setPassword] = useState('');
  const [activeTab, setActiveTab] = useState('offers');
  const [offers, setOffers] = useState<Offer[]>([]);
  const [categories, setCategories] = useState<Category[]>([]);
  const [banners, setBanners] = useState<Banner[]>([]);
  const [message, setMessage] = useState({ text: '', type: '' });
  const [editingOffer, setEditingOffer] = useState<Offer | null>(null);
  const [editingCategory, setEditingCategory] = useState<Category | null>(null);
  const [showOfferDialog, setShowOfferDialog] = useState(false);
  const [showCategoryDialog, setShowCategoryDialog] = useState(false);
  const [showBannerDialog, setShowBannerDialog] = useState(false);
  const [loading, setLoading] = useState(true);
  const [offerLogoImage, setOfferLogoImage] = useState('');
  const [offerVideoFile, setOfferVideoFile] = useState('');
  const [offerSteps, setOfferSteps] = useState<OfferStep[]>([]);
  const [bannerFile, setBannerFile] = useState('');
  const [bannerLink, setBannerLink] = useState('');
  const [bannerOrder, setBannerOrder] = useState('');

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
    
    const logoImageFilename = offerLogoImage || editingOffer?.logo_image || '';
    const videoFileFilename = offerVideoFile || editingOffer?.video_file || '';
    
    const offerData = {
      ...data,
      logo_image: logoImageFilename,
      video_file: videoFileFilename,
      steps: offerSteps,
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
        setShowOfferDialog(false);
        setEditingOffer(null);
        setOfferLogoImage('');
        setOfferVideoFile('');
        setOfferSteps([]);
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
        setShowCategoryDialog(false);
        setEditingCategory(null);
        fetchData();
      }
    } catch (error) {
      console.error('Error:', error);
    }
  };

  const handleBannerUpload = async (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    
    if (!bannerFile) {
      setMessage({ text: 'Please select a file', type: 'error' });
      return;
    }

    try {
      const bannerRes = await fetch('/api/banners', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          image_url: bannerFile,
          link_url: bannerLink,
          sort_order: Number(bannerOrder) || banners.length + 1,
          status: 'active',
        }),
      });
      
      if (bannerRes.ok) {
        setMessage({ text: 'Banner uploaded', type: 'success' });
        setBannerFile('');
        setBannerLink('');
        setBannerOrder('');
        setShowBannerDialog(false);
        fetchData();
      }
    } catch (error) {
      console.error('Error:', error);
    }
  };

  const openAddOffer = () => {
    setEditingOffer({} as Offer);
    setOfferLogoImage('');
    setOfferVideoFile('');
    setOfferSteps([]);
    setShowOfferDialog(true);
  };

  const openEditOffer = (offer: Offer) => {
    setEditingOffer(offer);
    setOfferLogoImage('');
    setOfferVideoFile('');
    setOfferSteps(offer.steps || []);
    setShowOfferDialog(true);
  };

  const openAddCategory = () => {
    setEditingCategory({} as Category);
    setShowCategoryDialog(true);
  };

  const openEditCategory = (category: Category) => {
    setEditingCategory(category);
    setShowCategoryDialog(true);
  };

  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'active':
        return <Badge variant="success" className="gap-1"><CheckCircle className="w-3 h-3" /> Active</Badge>;
      case 'expired':
        return <Badge variant="destructive" className="gap-1"><XCircle className="w-3 h-3" /> Expired</Badge>;
      case 'draft':
        return <Badge variant="warning" className="gap-1"><Clock className="w-3 h-3" /> Draft</Badge>;
      case 'inactive':
        return <Badge variant="secondary" className="gap-1"><XCircle className="w-3 h-3" /> Inactive</Badge>;
      default:
        return <Badge>{status}</Badge>;
    }
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-[var(--background)] text-[var(--foreground)] p-4">
        <Card className="max-w-[1400px] mx-auto">
          <CardContent className="p-4">
            <div className="flex items-center justify-between mb-6">
              <Skeleton className="w-48 h-10" />
              <div className="flex gap-2">
                <Skeleton className="w-20 h-10" />
                <Skeleton className="w-20 h-10" />
              </div>
            </div>
            <TabsList className="mb-6">
              <TabsTrigger value="offers">Offers</TabsTrigger>
              <TabsTrigger value="categories">Categories</TabsTrigger>
              <TabsTrigger value="banners">Banners</TabsTrigger>
            </TabsList>
            <TabsContent value="offers">
              <AdminTableSkeleton rows={8} />
            </TabsContent>
          </CardContent>
        </Card>
      </div>
    );
  }

  if (!isLoggedIn) {
    return (
      <div className="min-h-screen flex items-center justify-center p-4">
        <Card className="w-full max-w-[400px]">
          <CardHeader className="text-center">
            <CardTitle className="text-[1.4rem] text-[var(--primary)]">
              OS<span className="text-[var(--primary-light)]">M</span> Admin
            </CardTitle>
            <CardDescription>Enter password to access admin panel</CardDescription>
          </CardHeader>
          <CardContent>
          {message.text && (
            <div className={`p-3 rounded-lg mb-4 text-sm font-semibold ${message.type === 'error' ? 'bg-[var(--destructive)] text-white' : 'bg-[var(--success)] text-white'}`}>
              {message.text}
            </div>
          )}
          <form onSubmit={handleLogin}>
            <Input
              type="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              placeholder="Enter admin password"
              className="w-full mb-4 text-center"
            />
            <Button type="submit" className="w-full">
              Login
            </Button>
          </form>
          <Link href="/" className="block mt-4 text-[var(--primary)] text-sm text-center">← Back to Home</Link>
          </CardContent>
        </Card>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-[var(--background)] text-[var(--foreground)]">
      {/* Navbar */}
      <Card className="mx-4 mt-4 mb-4">
        <CardContent className="flex items-center justify-between p-4">
          <div className="font-extrabold text-[1.4rem] text-[var(--primary)]">
            OS<span className="text-[var(--primary-light)]">M</span> Admin
          </div>
          <div className="flex items-center gap-2">
            <ThemeToggle />
            <Link href="/">
              <Button variant="ghost" size="sm">View Site</Button>
            </Link>
            <Button variant="destructive" size="sm" onClick={handleLogout}>Logout</Button>
          </div>
        </CardContent>
      </Card>

      {message.text && (
        <div className={`mx-4 p-3 rounded-lg mb-4 text-sm font-semibold max-w-[1200px] ${message.type === 'error' ? 'bg-[var(--destructive)] text-white' : 'bg-[var(--success)] text-white'}`}>
          {message.text}
        </div>
      )}

      <div className="px-4 pb-8 max-w-[1400px] mx-auto">
        <Tabs value={activeTab} onValueChange={setActiveTab}>
          <TabsList className="mb-6">
            <TabsTrigger value="offers">
              <Tag className="w-4 h-4 mr-2" />
              Offers
              <span className="ml-2 px-1.5 py-0.5 text-xs rounded-full bg-[var(--primary-foreground)] text-[var(--primary)]">
                {offers.length}
              </span>
            </TabsTrigger>
            <TabsTrigger value="categories">
              <FolderCog className="w-4 h-4 mr-2" />
              Categories
              <span className="ml-2 px-1.5 py-0.5 text-xs rounded-full bg-[var(--primary-foreground)] text-[var(--primary)]">
                {categories.length}
              </span>
            </TabsTrigger>
            <TabsTrigger value="banners">
              <ImageIcon className="w-4 h-4 mr-2" />
              Banners
              <span className="ml-2 px-1.5 py-0.5 text-xs rounded-full bg-[var(--primary-foreground)] text-[var(--primary)]">
                {banners.length}
              </span>
            </TabsTrigger>
          </TabsList>

          {/* Offers Tab */}
          <TabsContent value="offers">
            <Card>
              <CardHeader className="flex flex-row items-center justify-between">
                <div>
                  <CardTitle>All Offers</CardTitle>
                  <CardDescription>Manage your offers and cashback deals</CardDescription>
                </div>
                <Button onClick={openAddOffer}>
                  <Plus className="w-4 h-4 mr-2" />
                  Add New Offer
                </Button>
              </CardHeader>
              <CardContent>
                <div className="overflow-x-auto">
                  <table className="w-full">
                    <thead>
                      <tr className="border-b bg-[var(--muted)]">
                        <th className="text-left py-3 px-4 text-xs font-bold text-[var(--muted-foreground)] uppercase tracking-wider">ID</th>
                        <th className="text-left py-3 px-4 text-xs font-bold text-[var(--muted-foreground)] uppercase tracking-wider">Title</th>
                        <th className="text-left py-3 px-4 text-xs font-bold text-[var(--muted-foreground)] uppercase tracking-wider">Brand</th>
                        <th className="text-left py-3 px-4 text-xs font-bold text-[var(--muted-foreground)] uppercase tracking-wider">Category</th>
                        <th className="text-left py-3 px-4 text-xs font-bold text-[var(--muted-foreground)] uppercase tracking-wider">Cashback</th>
                        <th className="text-left py-3 px-4 text-xs font-bold text-[var(--muted-foreground)] uppercase tracking-wider">Expires</th>
                        <th className="text-left py-3 px-4 text-xs font-bold text-[var(--muted-foreground)] uppercase tracking-wider">Status</th>
                        <th className="text-left py-3 px-4 text-xs font-bold text-[var(--muted-foreground)] uppercase tracking-wider">Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      {offers.map((offer, idx) => (
                        <tr key={offer._id} className={`border-b hover:bg-[var(--accent)] transition-colors ${idx % 2 === 0 ? 'bg-[var(--background)]' : 'bg-[var(--muted)]/30'}`}>
                          <td className="py-3 px-4 text-sm font-mono text-[var(--text-sub)]">{offer._id.slice(-6)}</td>
                          <td className="py-3 px-4 text-sm max-w-[200px] truncate font-medium">{offer.title}</td>
                          <td className="py-3 px-4 text-sm">{offer.brand_emoji} {offer.brand_name}</td>
                          <td className="py-3 px-4 text-sm">
                            <Badge variant="outline">{offer.category}</Badge>
                          </td>
                          <td className="py-3 px-4 text-sm font-bold text-[var(--success)]">
                            {offer.cashback_type === 'flat' ? `₹${offer.max_cashback}` : `${offer.cashback_rate}%`}
                          </td>
                          <td className="py-3 px-4 text-sm text-[var(--text-sub)]">
                            {new Date(offer.expiry_date).toLocaleDateString('en-GB')}
                          </td>
                          <td className="py-3 px-4">{getStatusBadge(offer.status)}</td>
                          <td className="py-3 px-4">
                            <div className="flex gap-2">
                              <Button variant="outline" size="sm" onClick={() => openEditOffer(offer)}>
                                <Edit className="w-3 h-3 mr-1" />
                                Edit
                              </Button>
                              <Button variant="destructive" size="sm" onClick={() => deleteOffer(offer._id)}>
                                <Trash2 className="w-3 h-3" />
                              </Button>
                            </div>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                  {offers.length === 0 && (
                    <div className="text-center py-12 text-[var(--text-sub)]">
                      <Tag className="w-12 h-12 mx-auto mb-3 opacity-50" />
                      <p>No offers yet. Add your first offer!</p>
                    </div>
                  )}
                </div>
              </CardContent>
            </Card>
          </TabsContent>

          {/* Categories Tab */}
          <TabsContent value="categories">
            <Card>
              <CardHeader className="flex flex-row items-center justify-between">
                <div>
                  <CardTitle>Manage Categories</CardTitle>
                  <CardDescription>Organize your offers by categories</CardDescription>
                </div>
                <Button onClick={openAddCategory}>
                  <Plus className="w-4 h-4 mr-2" />
                  Add Category
                </Button>
              </CardHeader>
              <CardContent>
                <table className="w-full">
                  <thead>
                    <tr className="border-b bg-[var(--muted)]">
                      <th className="text-left py-3 px-4 text-xs font-bold text-[var(--muted-foreground)] uppercase tracking-wider">Order</th>
                      <th className="text-left py-3 px-4 text-xs font-bold text-[var(--muted-foreground)] uppercase tracking-wider">Emoji</th>
                      <th className="text-left py-3 px-4 text-xs font-bold text-[var(--muted-foreground)] uppercase tracking-wider">Name</th>
                      <th className="text-left py-3 px-4 text-xs font-bold text-[var(--muted-foreground)] uppercase tracking-wider">Offers</th>
                      <th className="text-left py-3 px-4 text-xs font-bold text-[var(--muted-foreground)] uppercase tracking-wider">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    {categories.map((cat, idx) => (
                      <tr key={cat._id} className={`border-b hover:bg-[var(--accent)] transition-colors ${idx % 2 === 0 ? 'bg-[var(--background)]' : 'bg-[var(--muted)]/30'}`}>
                        <td className="py-3 px-4 text-sm font-mono text-[var(--text-sub)]">{cat.sort_order}</td>
                        <td className="py-3 px-4 text-2xl">{cat.emoji}</td>
                        <td className="py-3 px-4 text-sm font-semibold">{cat.name}</td>
                        <td className="py-3 px-4 text-sm text-[var(--text-sub)]">
                          {offers.filter(o => o.category === cat.name).length}
                        </td>
                        <td className="py-3 px-4">
                          <div className="flex gap-2">
                            <Button variant="outline" size="sm" onClick={() => openEditCategory(cat)}>
                              <Edit className="w-3 h-3 mr-1" />
                              Edit
                            </Button>
                            <Button variant="destructive" size="sm" onClick={() => deleteCategory(cat._id)}>
                              <Trash2 className="w-3 h-3" />
                            </Button>
                          </div>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
                {categories.length === 0 && (
                  <div className="text-center py-12 text-[var(--text-sub)]">
                    <FolderCog className="w-12 h-12 mx-auto mb-3 opacity-50" />
                    <p>No categories yet. Add your first category!</p>
                  </div>
                )}
              </CardContent>
            </Card>
          </TabsContent>

          {/* Banners Tab */}
          <TabsContent value="banners">
            <Card>
              <CardHeader className="flex flex-row items-center justify-between">
                <div>
                  <CardTitle>Manage Banners</CardTitle>
                  <CardDescription>Upload and manage promotional banners</CardDescription>
                </div>
                <Button onClick={() => setShowBannerDialog(true)}>
                  <Upload className="w-4 h-4 mr-2" />
                  Upload Banner
                </Button>
              </CardHeader>
              <CardContent>
                <div className="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
                  {banners.map((banner) => (
                    <div key={banner._id} className="border border-[var(--border-color)] rounded-xl overflow-hidden hover:shadow-lg transition-shadow">
                      <div className="relative">
                        <img src={`/api/files/${banner.image_url}`} alt="Banner" className="w-full h-[140px] object-cover" />
                        <div className="absolute top-2 right-2">
                          {getStatusBadge(banner.status)}
                        </div>
                      </div>
                      <div className="p-3">
                        <div className="flex items-center justify-between mb-2">
                          <span className="text-xs text-[var(--text-sub)]">Order: {banner.sort_order}</span>
                          {banner.link_url && (
                            <a href={banner.link_url} target="_blank" rel="noopener noreferrer" className="text-xs text-[var(--primary)] hover:underline flex items-center gap-1">
                              <Eye className="w-3 h-3" /> View
                            </a>
                          )}
                        </div>
                        <div className="flex gap-2">
                          <Button variant="outline" size="sm" className="flex-1" onClick={() => toggleBannerStatus(banner)}>
                            {banner.status === 'active' ? (
                              <>
                                <XCircle className="w-3 h-3 mr-1" /> Disable
                              </>
                            ) : (
                              <>
                                <CheckCircle className="w-3 h-3 mr-1" /> Enable
                              </>
                            )}
                          </Button>
                          <Button variant="destructive" size="sm" onClick={() => deleteBanner(banner._id)}>
                            <Trash2 className="w-3 h-3" />
                          </Button>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
                {banners.length === 0 && (
                  <div className="text-center py-12 text-[var(--text-sub)]">
                    <ImageIcon className="w-12 h-12 mx-auto mb-3 opacity-50" />
                    <p>No banners yet. Upload your first banner!</p>
                  </div>
                )}
              </CardContent>
            </Card>
          </TabsContent>
        </Tabs>
      </div>

      {/* Offer Dialog */}
      <Dialog open={showOfferDialog} onOpenChange={setShowOfferDialog}>
        <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
          <DialogHeader>
            <DialogTitle>{editingOffer?._id ? 'Edit Offer' : 'Add New Offer'}</DialogTitle>
          </DialogHeader>
          <form onSubmit={handleSaveOffer}>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 py-4">
              <div className="space-y-2">
                <label className="text-sm font-medium">Title *</label>
                <Input name="title" defaultValue={editingOffer?.title} placeholder="Offer Title" required />
              </div>
              <div className="space-y-2">
                <label className="text-sm font-medium">Brand Name *</label>
                <Input name="brand_name" defaultValue={editingOffer?.brand_name} placeholder="Brand Name" required />
              </div>
              <div className="space-y-2">
                <label className="text-sm font-medium">Brand Emoji</label>
                <Input name="brand_emoji" defaultValue={editingOffer?.brand_emoji || '🎁'} placeholder="🎁" />
              </div>
              <div className="space-y-2">
                <label className="text-sm font-medium">Category</label>
                <select 
                  name="category" 
                  defaultValue={editingOffer?.category || 'General'}
                  className="w-full h-10 px-3 rounded-lg border border-[var(--border-color)] bg-[var(--background)] text-[var(--foreground)]"
                >
                  {categories.map(c => <option key={c._id} value={c.name}>{c.emoji} {c.name}</option>)}
                </select>
              </div>
              <div className="space-y-2">
                <label className="text-sm font-medium">Logo Image</label>
                <FileUpload
                  accept="image/*"
                  label=""
                  value={offerLogoImage || editingOffer?.logo_image || ''}
                  onChange={(filename) => setOfferLogoImage(filename)}
                />
              </div>
              <div className="space-y-2">
                <label className="text-sm font-medium">Video File</label>
                <FileUpload
                  accept="video/*"
                  label=""
                  value={offerVideoFile || editingOffer?.video_file || ''}
                  onChange={(filename) => setOfferVideoFile(filename)}
                  maxSize={50}
                />
              </div>
              <div className="space-y-2 md:col-span-2">
                <label className="text-sm font-medium">Description</label>
                <Textarea name="description" defaultValue={editingOffer?.description} placeholder="Offer description" />
              </div>
              <div className="space-y-2">
                <label className="text-sm font-medium">Min Order Amount</label>
                <Input name="min_order_amount" type="number" defaultValue={editingOffer?.min_order_amount || 0} placeholder="0" />
              </div>
              <div className="space-y-2">
                <label className="text-sm font-medium">Max Cashback (₹)</label>
                <Input name="max_cashback" type="number" defaultValue={editingOffer?.max_cashback || 0} placeholder="0" />
              </div>
              <div className="space-y-2">
                <label className="text-sm font-medium">Cashback Rate (%)</label>
                <Input name="cashback_rate" type="number" defaultValue={editingOffer?.cashback_rate || 0} placeholder="0" />
              </div>
              <div className="space-y-2">
                <label className="text-sm font-medium">Cashback Type</label>
                <select 
                  name="cashback_type" 
                  defaultValue={editingOffer?.cashback_type || 'flat'}
                  className="w-full h-10 px-3 rounded-lg border border-[var(--border-color)] bg-[var(--background)] text-[var(--foreground)]"
                >
                  <option value="flat">Flat Amount</option>
                  <option value="percentage">Percentage</option>
                </select>
              </div>
              <div className="space-y-2">
                <label className="text-sm font-medium">Expiry Date</label>
                <Input name="expiry_date" type="date" defaultValue={editingOffer?.expiry_date?.split('T')[0]} />
              </div>
              <div className="space-y-2">
                <label className="text-sm font-medium">Promo Code</label>
                <Input name="promo_code" defaultValue={editingOffer?.promo_code} placeholder="PROMO123" />
              </div>
              <div className="space-y-2">
                <label className="text-sm font-medium">Redirect URL</label>
                <Input name="redirect_url" defaultValue={editingOffer?.redirect_url} placeholder="https://..." />
              </div>
              <div className="space-y-2">
                <label className="text-sm font-medium">Link 2 (Optional)</label>
                <Input name="link2" defaultValue={editingOffer?.link2} placeholder="https://..." />
              </div>
              <div className="space-y-2">
                <label className="text-sm font-medium">Claimed Count</label>
                <Input name="claimed_count" type="number" defaultValue={editingOffer?.claimed_count || 0} placeholder="0" />
              </div>
              <div className="space-y-2">
                <label className="text-sm font-medium">Rating</label>
                <Input name="rating" type="number" step="0.1" defaultValue={editingOffer?.rating || 0} placeholder="0" />
              </div>
              <div className="space-y-2">
                <label className="text-sm font-medium">Status</label>
                <select 
                  name="status" 
                  defaultValue={editingOffer?.status || 'active'}
                  className="w-full h-10 px-3 rounded-lg border border-[var(--border-color)] bg-[var(--background)] text-[var(--foreground)]"
                >
                  <option value="active">Active</option>
                  <option value="expired">Expired</option>
                  <option value="draft">Draft</option>
                </select>
              </div>
              <div className="space-y-2">
                <label className="text-sm font-medium">Payout Type</label>
                <select 
                  name="payout_type" 
                  defaultValue={editingOffer?.payout_type || 'instant'}
                  className="w-full h-10 px-3 rounded-lg border border-[var(--border-color)] bg-[var(--background)] text-[var(--foreground)]"
                >
                  <option value="instant">Instant</option>
                  <option value="24-72h">24-72 Hours</option>
                </select>
              </div>
              <div className="md:col-span-2 flex flex-wrap gap-4">
                <label className="flex items-center gap-2 text-sm font-medium">
                  <input type="checkbox" name="is_featured" defaultChecked={editingOffer?.is_featured} /> Featured
                </label>
                <label className="flex items-center gap-2 text-sm font-medium">
                  <input type="checkbox" name="is_verified" defaultChecked={editingOffer?.is_verified} /> Verified
                </label>
                <label className="flex items-center gap-2 text-sm font-medium">
                  <input type="checkbox" name="is_popular" defaultChecked={editingOffer?.is_popular} /> Popular
                </label>
              </div>
              <div className="md:col-span-2 space-y-3">
                <div className="flex items-center justify-between">
                  <label className="text-sm font-medium">Steps (How to Claim)</label>
                  <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={() => setOfferSteps([...offerSteps, { step_number: offerSteps.length + 1, step_title: '', step_description: '', step_time: '' }])}
                  >
                    <Upload className="w-4 h-4 mr-1" /> Add Step
                  </Button>
                </div>
                {offerSteps.map((step, index) => (
                  <div key={index} className="flex gap-2 items-start p-3 border border-[var(--border-color)] rounded-lg bg-[var(--muted)]">
                    <div className="flex-shrink-0 w-6 h-6 rounded-full bg-[var(--primary)] text-white flex items-center justify-center text-xs font-bold">
                      {step.step_number}
                    </div>
                    <div className="flex-1 space-y-2">
                      <Input
                        placeholder="Step title"
                        value={step.step_title}
                        onChange={(e) => {
                          const newSteps = [...offerSteps];
                          newSteps[index].step_title = e.target.value;
                          setOfferSteps(newSteps);
                        }}
                      />
                      <Input
                        placeholder="Description (optional)"
                        value={step.step_description || ''}
                        onChange={(e) => {
                          const newSteps = [...offerSteps];
                          newSteps[index].step_description = e.target.value;
                          setOfferSteps(newSteps);
                        }}
                      />
                      <Input
                        placeholder="Time (e.g., 2-3 days)"
                        value={step.step_time || ''}
                        onChange={(e) => {
                          const newSteps = [...offerSteps];
                          newSteps[index].step_time = e.target.value;
                          setOfferSteps(newSteps);
                        }}
                      />
                    </div>
                    <Button
                      type="button"
                      variant="destructive"
                      size="sm"
                      onClick={() => setOfferSteps(offerSteps.filter((_, i) => i !== index).map((s, i) => ({ ...s, step_number: i + 1 })))}
                    >
                      X
                    </Button>
                  </div>
                ))}
              </div>
            </div>
            <DialogFooter>
              <Button type="button" variant="secondary" onClick={() => setShowOfferDialog(false)}>Cancel</Button>
              <Button type="submit">Save</Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      {/* Category Dialog */}
      <Dialog open={showCategoryDialog} onOpenChange={setShowCategoryDialog}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>{editingCategory?._id ? 'Edit Category' : 'Add Category'}</DialogTitle>
          </DialogHeader>
          <form onSubmit={handleSaveCategory}>
            <div className="space-y-4 py-4">
              <div className="space-y-2">
                <label className="text-sm font-medium">Category Name</label>
                <Input name="name" defaultValue={editingCategory?.name} placeholder="Category Name" required />
              </div>
              <div className="space-y-2">
                <label className="text-sm font-medium">Emoji</label>
                <Input name="emoji" defaultValue={editingCategory?.emoji || '📌'} placeholder="📌" />
              </div>
              <div className="space-y-2">
                <label className="text-sm font-medium">Sort Order</label>
                <Input name="sort_order" type="number" defaultValue={editingCategory?.sort_order || categories.length + 1} placeholder="1" />
              </div>
            </div>
            <DialogFooter>
              <Button type="button" variant="secondary" onClick={() => setShowCategoryDialog(false)}>Cancel</Button>
              <Button type="submit">Save</Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      {/* Banner Upload Dialog */}
      <Dialog open={showBannerDialog} onOpenChange={setShowBannerDialog}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Upload Banner</DialogTitle>
          </DialogHeader>
          <form onSubmit={handleBannerUpload}>
            <div className="space-y-4 py-4">
              <div className="space-y-2">
                <label className="text-sm font-medium">Banner Image</label>
                <FileUpload
                  accept="image/*"
                  label=""
                  value={bannerFile}
                  onChange={(filename) => setBannerFile(filename)}
                />
              </div>
              <div className="space-y-2">
                <label className="text-sm font-medium">Link URL (Optional)</label>
                <Input 
                  type="text" 
                  value={bannerLink}
                  onChange={(e) => setBannerLink(e.target.value)}
                  placeholder="https://example.com" 
                />
              </div>
              <div className="space-y-2">
                <label className="text-sm font-medium">Sort Order</label>
                <Input 
                  type="number" 
                  value={bannerOrder}
                  onChange={(e) => setBannerOrder(e.target.value)}
                  defaultValue={banners.length + 1}
                  placeholder="1" 
                />
              </div>
            </div>
            <DialogFooter>
              <Button type="button" variant="secondary" onClick={() => setShowBannerDialog(false)}>Cancel</Button>
              <Button type="submit" disabled={!bannerFile}>Upload</Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </div>
  );
}
