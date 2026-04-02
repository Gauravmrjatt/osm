'use client';

import { useState, useEffect } from 'react';
import { useParams } from 'next/navigation';
import Link from 'next/link';
import { ArrowLeft, Copy, CheckCircle, Clock, DollarSign, ExternalLink, X } from 'lucide-react';
import { formatNumber, isExpired, getDaysRemaining } from '@/lib/utils';
import { OfferDetailSkeleton } from '@/components/ui/skeleton';

interface OfferStep {
  step_number: number;
  step_title: string;
  step_description?: string;
  step_time?: string;
}

interface OfferTerm {
  term_text: string;
}

interface Offer {
  _id: string;
  title: string;
  description?: string;
  brand_name: string;
  brand_emoji: string;
  logo_image?: string;
  video_file?: string;
  video_source?: 'cloudinary' | 'local';
  category: string;
  max_cashback: number;
  cashback_rate: number;
  cashback_type: string;
  promo_code?: string;
  redirect_url?: string;
  link2?: string;
  claimed_count: number;
  rating: number;
  is_verified: boolean;
  is_popular: boolean;
  expiry_date?: string;
  payout_type: string;
  steps: OfferStep[];
  terms: OfferTerm[];
}

export default function OfferPage() {
  const params = useParams();
  const [offer, setOffer] = useState<Offer | null>(null);
  const [loading, setLoading] = useState(true);
  const [modalOpen, setModalOpen] = useState(false);
  const [redirectUrl, setRedirectUrl] = useState('');
  const [copied, setCopied] = useState(false);

  useEffect(() => {
    async function fetchOffer() {
      try {
        const res = await fetch(`/api/offer/${params.id}`);
        if (res.ok) {
          const data = await res.json();
          setOffer(data);
        }
      } catch (error) {
        console.error('Error fetching offer:', error);
      } finally {
        setLoading(false);
      }
    }

    if (params.id) {
      fetchOffer();
    }
  }, [params.id]);

  const openModal = (url: string) => {
    setRedirectUrl(url);
    setModalOpen(true);
  };

  const closeModal = () => {
    setModalOpen(false);
  };

  const confirmRedirect = () => {
    closeModal();
    if (redirectUrl) {
      window.open(redirectUrl, '_blank');
    } else {
      alert(`Redirecting to ${offer?.brand_name}… Cashback tracking activated!`);
    }
  };

  const copyCode = () => {
    if (offer?.promo_code) {
      navigator.clipboard.writeText(offer.promo_code);
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    }
  };

  if (loading) {
    return (
      <div className="min-h-screen relative">
        <div className="fixed inset-0 dotted-grid dotted-grid-mask pointer-events-none z-0" />
        <div className="fixed -top-1/2 left-1/2 -translate-x-1/2 w-[120vmin] h-[120vmin] rounded-full radial-spotlight-blue blur-[50px] pointer-events-none z-0" />
        <div className="max-w-[600px] mx-auto p-4 relative z-10">
          <OfferDetailSkeleton />
        </div>
      </div>
    );
  }

  if (!offer) {
    return (
      <div className="min-h-screen flex flex-col items-center justify-center">
        <p className="text-[var(--text-sub)] mb-4">Offer not found</p>
        <Link href="/" className="text-[var(--primary-light)] font-semibold">
          Go back home
        </Link>
      </div>
    );
  }

  const expired = offer.expiry_date ? isExpired(offer.expiry_date) : false;
  const daysLeft = offer.expiry_date ? getDaysRemaining(offer.expiry_date) : 0;
  const cashbackDisplay = offer.cashback_type === 'flat' 
    ? `₹${formatNumber(offer.max_cashback)}` 
    : `${offer.cashback_rate}%`;

  return (
    <div className="min-h-screen">
      {/* Navbar */}
      <nav className="sticky top-0 z-50 backdrop-blur-xl border-b border-[var(--border)] bg-[rgba(11,15,20,0.9)] h-[60px] flex items-center justify-between px-4">
        <Link href="/" className="flex items-center gap-2 bg-[var(--accent)] text-[var(--foreground)] px-3.5 py-2 rounded-xl text-[0.8rem] font-semibold transition-all hover:bg-[var(--primary)]">
          <ArrowLeft className="w-4 h-4" />
          Back
        </Link>
        <div className="font-extrabold text-[1.3rem] tracking-tight">
          OS<span className="text-[var(--primary-light)]">M</span>
        </div>
        <div className="w-10"></div>
      </nav>

      <div className="max-w-[600px] mx-auto px-4 pb-[120px] flex flex-col gap-4">
        {/* Video/Image */}
        {offer.video_file ? (
          <div className="rounded-[var(--radius)] overflow-hidden">
            <video controls className="w-full h-auto bg-black">
              <source src={offer.video_source === 'cloudinary' ? offer.video_file : `/api/files/${offer.video_file}`} type="video/mp4" />
            </video>
          </div>
        ) : offer.logo_image ? (
          <div className="rounded-[var(--radius)] overflow-hidden">
            <img src={`/api/files/${offer.logo_image}`} alt={offer.title} className="w-full h-auto" />
          </div>
        ) : null}

        {/* Brand Info */}
        <div className="bg-[var(--bg-card)] rounded-[var(--radius)] p-4 flex items-center gap-3.5 border border-[var(--border)]">
          <div className="w-14 h-14 rounded-[0.875rem] bg-[var(--accent)] flex items-center justify-center text-[1.8rem] flex-shrink-0">
            {offer.logo_image ? (
              <img src={`/api/files/${offer.logo_image}`} alt={offer.brand_name} className="w-full h-full object-cover rounded-[0.875rem]" />
            ) : (
              offer.brand_emoji
            )}
          </div>
          <div className="flex-1">
            <h2 className="font-bold text-[1.1rem] text-[var(--text)]">{offer.brand_name}</h2>
            <p className="text-[0.72rem] text-[var(--text-sub)] mt-1">
              {offer.category} · {formatNumber(offer.claimed_count)}+ claimed
            </p>
          </div>
          <div className="flex flex-row gap-1.5 flex-wrap justify-end">
            {offer.is_verified && (
              <span className="text-[0.62rem] font-bold bg-[rgba(0,210,106,0.12)] text-[var(--green)] px-2.5 py-1 rounded-lg">
                ✓ Verified
              </span>
            )}
            {offer.is_popular && (
              <span className="text-[0.62rem] font-bold bg-[rgba(30,107,255,0.12)] text-[var(--primary-light)] px-2.5 py-1 rounded-lg">
                🔥 Popular
              </span>
            )}
            {expired && (
              <span className="text-[0.62rem] font-bold bg-[var(--destructive)]/10 text-[var(--destructive)] px-2.5 py-1 rounded-lg">
                ❌ Expired
              </span>
            )}
          </div>
        </div>

        {/* Description */}
        <div className="bg-[var(--bg-card)] rounded-[var(--radius)] p-4.5 border border-[var(--border)]">
          <h3 className="font-bold text-[0.95rem] text-[var(--text)] mb-3.5 flex items-center gap-2">
            <CheckCircle className="w-4.5 h-4.5 text-[var(--primary-light)]" />
            About This Offer
          </h3>
          <p className="text-[0.82rem] text-[var(--text-sub)] leading-relaxed whitespace-pre-line">
            {offer.description}
          </p>
          <div className="flex gap-2.5 mt-4">
            <div className="flex-1 bg-[var(--accent)] rounded-xl p-3 text-center border border-[var(--border)]">
              <div className="font-extrabold text-[1rem] text-[var(--primary-light)]">
                {formatNumber(offer.claimed_count)}
              </div>
              <div className="text-[0.6rem] text-[var(--text-sub)] mt-0.5">Claimed</div>
            </div>
            <div className="flex-1 bg-[var(--accent)] rounded-xl p-3 text-center border border-[var(--border)]">
              <div className="font-extrabold text-[1rem] text-[var(--primary-light)]">
                {cashbackDisplay}
              </div>
              <div className="text-[0.6rem] text-[var(--text-sub)] mt-0.5">Cashback</div>
            </div>
            <div className="flex-1 bg-[var(--accent)] rounded-xl p-3 text-center border border-[var(--border)]">
              <div className="font-extrabold text-[1rem] text-[var(--primary-light)]">
                {offer.payout_type === 'instant' ? '⚡' : '⏱'}
              </div>
              <div className="text-[0.6rem] text-[var(--text-sub)] mt-0.5">
                {offer.payout_type === 'instant' ? 'Instant' : '24-72h'}
              </div>
            </div>
          </div>
        </div>

        {/* CTA Buttons */}
        <div className="flex flex-col gap-2.5 mb-4">
          {expired ? (
            <button className="w-full py-4 rounded-[0.875rem] bg-gradient-to-r from-[var(--muted)] to-[var(--muted-foreground)] text-white font-bold text-[0.95rem] cursor-not-allowed flex items-center justify-center gap-2.5" disabled>
              <X className="w-4.5 h-4.5" />
              Offer Expired
            </button>
          ) : (
            <>
              {offer.redirect_url && (
                <button 
                  onClick={() => openModal(offer.redirect_url!)}
                  className="w-full py-4 rounded-[0.875rem] bg-gradient-to-r from-[var(--primary)] to-[var(--primary-light)] text-white font-bold text-[0.95rem] shadow-[var(--glow)] flex items-center justify-center gap-2.5 transition-all hover:-translate-y-0.5 hover:shadow-[0_6px_24px_rgba(30,107,255,0.5)]"
                >
                  <ExternalLink className="w-4.5 h-4.5" />
                  Claim {cashbackDisplay} Now
                </button>
              )}
              {offer.link2 && (
                <button 
                  onClick={() => openModal(offer.link2!)}
                  className="w-full py-4 rounded-[0.875rem] bg-gradient-to-r from-[var(--primary)] to-[var(--primary-light)] text-white font-bold text-[0.95rem] shadow-[var(--glow)] flex items-center justify-center gap-2.5 transition-all hover:-translate-y-0.5 hover:shadow-[0_6px_24px_rgba(30,107,255,0.5)]"
                >
                  <ExternalLink className="w-4.5 h-4.5" />
                  Tracking Link
                </button>
              )}
              {!offer.redirect_url && !offer.link2 && (
                <button 
                  onClick={() => openModal('')}
                  className="w-full py-4 rounded-[0.875rem] bg-gradient-to-r from-[var(--primary)] to-[var(--primary-light)] text-white font-bold text-[0.95rem] shadow-[var(--glow)] flex items-center justify-center gap-2.5"
                >
                  <ExternalLink className="w-4.5 h-4.5" />
                  Claim {cashbackDisplay} Now
                </button>
              )}
            </>
          )}

          {!expired && offer.promo_code && (
            <div className="bg-[rgba(30,107,255,0.1)] border border-dashed border-[rgba(30,107,255,0.3)] rounded-[0.625rem] p-3 flex items-center justify-between mt-3">
              <div>
                <div className="text-[0.65rem] text-[var(--text-sub)]">Promo Code</div>
                <div className="font-extrabold text-[1.1rem] text-[var(--primary-light)] tracking-wider">
                  {offer.promo_code}
                </div>
              </div>
              <button 
                onClick={copyCode}
                className="bg-[var(--primary)] text-white px-3 py-1.5 rounded-md text-[0.7rem] font-semibold"
              >
                {copied ? 'Copied!' : 'Copy'}
              </button>
            </div>
          )}
        </div>

        {/* Steps */}
        <div className="bg-[var(--bg-card)] rounded-[var(--radius)] p-4.5 border border-[var(--border)]">
          <h3 className="font-bold text-[0.95rem] text-[var(--text)] mb-3.5 flex items-center gap-2">
            <Clock className="w-4.5 h-4.5 text-[var(--primary-light)]" />
            How to Claim
          </h3>
          <div className="flex flex-col gap-0">
            {offer.steps && offer.steps.length > 0 ? (
              offer.steps.map((step, index) => (
                <div key={index} className="flex gap-3.5 relative">
                  <div className="flex flex-col items-center w-8 flex-shrink-0">
                    <div className="w-8 h-8 rounded-full bg-[rgba(30,107,255,0.15)] text-[var(--primary-light)] flex items-center justify-center font-extrabold text-[0.8rem] border-2 border-[var(--primary)]">
                      {step.step_number}
                    </div>
                    {index < offer.steps!.length - 1 && (
                      <div className="w-0.5 flex-1 bg-[rgba(30,107,255,0.2)] min-h-5 my-1"></div>
                    )}
                  </div>
                  <div className="flex-1 pb-5">
                    <div className="font-semibold text-[0.85rem] text-[var(--text)]">{step.step_title}</div>
                    <div className="text-[0.72rem] text-[var(--text-sub)] leading-relaxed mt-0.5">{step.step_description}</div>
                    {step.step_time && (
                      <div className="inline-flex items-center gap-1 bg-[rgba(30,107,255,0.1)] text-[var(--primary-light)] text-[0.62rem] font-semibold px-2 py-1 rounded-md mt-1.5">
                        <Clock className="w-2.5 h-2.5" />
                        {step.step_time}
                      </div>
                    )}
                  </div>
                </div>
              ))
            ) : (
              <>
                <div className="flex gap-3.5">
                  <div className="flex flex-col items-center w-8 flex-shrink-0">
                    <div className="w-8 h-8 rounded-full bg-[var(--primary)] text-white flex items-center justify-center font-extrabold text-[0.8rem]">
                      ✓
                    </div>
                    <div className="w-0.5 flex-1 bg-[rgba(30,107,255,0.2)] min-h-5 my-1"></div>
                  </div>
                  <div className="flex-1 pb-5">
                    <div className="font-semibold text-[0.85rem] text-[var(--text)]">Open the Offer</div>
                    <div className="text-[0.72rem] text-[var(--text-sub)] mt-0.5">Tap "Claim Now" to be redirected to the merchant.</div>
                  </div>
                </div>
                <div className="flex gap-3.5">
                  <div className="flex flex-col items-center w-8 flex-shrink-0">
                    <div className="w-8 h-8 rounded-full bg-[rgba(30,107,255,0.15)] text-[var(--primary-light)] flex items-center justify-center font-extrabold text-[0.8rem] border-2 border-[var(--primary)]">
                      2
                    </div>
                    <div className="w-0.5 flex-1 bg-[rgba(30,107,255,0.2)] min-h-5 my-1"></div>
                  </div>
                  <div className="flex-1 pb-5">
                    <div className="font-semibold text-[0.85rem] text-[var(--text)]">Apply Promo Code</div>
                    <div className="text-[0.72rem] text-[var(--text-sub)] mt-0.5">
                      Use code <strong>{offer.promo_code || 'OSM'}</strong>
                    </div>
                  </div>
                </div>
                <div className="flex gap-3.5">
                  <div className="flex flex-col items-center w-8 flex-shrink-0">
                    <div className="w-8 h-8 rounded-full bg-[rgba(30,107,255,0.15)] text-[var(--primary-light)] flex items-center justify-center font-extrabold text-[0.8rem]">
                      🎉
                    </div>
                  </div>
                  <div className="flex-1">
                    <div className="font-semibold text-[0.85rem] text-[var(--text)]">Get Cashback!</div>
                    <div className="text-[0.72rem] text-[var(--text-sub)] mt-0.5">{cashbackDisplay} credited to your wallet.</div>
                  </div>
                </div>
              </>
            )}
          </div>
        </div>

        {/* Terms */}
        {offer.terms && offer.terms.length > 0 && (
          <div className="bg-[var(--bg-card)] rounded-[var(--radius)] p-4.5 border border-[var(--border)]">
            <h3 className="font-bold text-[0.95rem] text-[var(--text)] mb-3.5 flex items-center gap-2">
              <CheckCircle className="w-4.5 h-4.5 text-[var(--primary-light)]" />
              Terms & Conditions
            </h3>
            <ul className="text-[0.72rem] text-[var(--text-sub)] leading-relaxed list-disc list-inside space-y-1.5">
              {offer.terms.map((term, index) => (
                <li key={index}>{term.term_text}</li>
              ))}
            </ul>
          </div>
        )}
      </div>

      {/* Redirect Modal */}
      {modalOpen && (
        <div 
          className="fixed inset-0 z-[1000] bg-black/80 backdrop-blur-lg flex items-center justify-center p-5 animate-popIn"
          onClick={closeModal}
        >
          <div 
            className="bg-[var(--bg-card)] rounded-[1.5rem] max-w-[400px] w-full p-7 text-center border border-[var(--border)]"
            onClick={e => e.stopPropagation()}
          >
            <div className="text-[2.5rem] mb-3">🚀</div>
            <h3 className="font-bold text-[1.2rem] text-[var(--text)] mb-2">You're leaving OSM</h3>
            <p className="text-[0.8rem] text-[var(--text-sub)] leading-relaxed mb-5">
              You'll be redirected to <strong>{offer.brand_name}</strong>. Make sure to complete all the steps so your <strong>{cashbackDisplay} cashback</strong> gets tracked!
            </p>
            <div className="text-[0.75rem] text-[var(--warning)] mt-2.5 p-2.5 bg-[var(--warning)]/10 rounded-lg">
              ⚠️ Please read all steps and Terms & Conditions carefully before proceeding. Cashback will only be credited if all instructions are followed correctly.
            </div>
            <div className="flex gap-2.5 mt-5">
              <button 
                onClick={closeModal}
                className="flex-1 py-3 border border-[var(--border-color)] rounded-xl bg-transparent text-[var(--text-sub)] font-semibold text-[0.85rem]"
              >
                Cancel
              </button>
              <button 
                onClick={confirmRedirect}
                className="flex-1 py-3 rounded-xl bg-gradient-to-r from-[var(--primary)] to-[var(--primary-light)] text-white font-bold text-[0.85rem]"
              >
                Continue
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
