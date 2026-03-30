import mongoose, { Schema, Document } from 'mongoose';

export interface IOfferStep {
  step_number: number;
  step_title: string;
  step_description?: string;
  step_time?: string;
  is_completed?: boolean;
}

export interface IOfferTerm {
  term_text: string;
  sort_order: number;
}

export interface IOffer extends Document {
  title: string;
  description?: string;
  brand_name: string;
  brand_emoji: string;
  logo_image?: string;
  video_file?: string;
  category: string;
  min_order_amount: number;
  max_cashback: number;
  cashback_rate: number;
  cashback_type: 'flat' | 'percentage';
  min_amount?: number;
  max_amount?: number;
  expiry_date?: Date;
  promo_code?: string;
  redirect_url?: string;
  link2?: string;
  claimed_count: number;
  rating: number;
  is_featured: boolean;
  is_verified: boolean;
  is_popular: boolean;
  status: 'active' | 'expired' | 'draft';
  payout_type: 'instant' | '24-72h';
  steps: IOfferStep[];
  terms: IOfferTerm[];
  created_at: Date;
  updated_at: Date;
}

const OfferStepSchema = new Schema<IOfferStep>({
  step_number: { type: Number, required: true },
  step_title: { type: String, required: true },
  step_description: String,
  step_time: String,
  is_completed: { type: Boolean, default: false },
});

const OfferTermSchema = new Schema<IOfferTerm>({
  term_text: { type: String, required: true },
  sort_order: { type: Number, default: 0 },
});

const OfferSchema = new Schema<IOffer>({
  title: { type: String, required: true },
  description: String,
  brand_name: { type: String, required: true },
  brand_emoji: { type: String, default: '🎁' },
  logo_image: String,
  video_file: String,
  category: { type: String, default: 'General' },
  min_order_amount: { type: Number, default: 0 },
  max_cashback: { type: Number, default: 0 },
  cashback_rate: { type: Number, default: 0 },
  cashback_type: { type: String, enum: ['flat', 'percentage'], default: 'flat' },
  min_amount: Number,
  max_amount: Number,
  expiry_date: Date,
  promo_code: String,
  redirect_url: String,
  link2: String,
  claimed_count: { type: Number, default: 0 },
  rating: { type: Number, default: 0 },
  is_featured: { type: Boolean, default: false },
  is_verified: { type: Boolean, default: false },
  is_popular: { type: Boolean, default: false },
  status: { type: String, enum: ['active', 'expired', 'draft'], default: 'active' },
  payout_type: { type: String, enum: ['instant', '24-72h'], default: 'instant' },
  steps: [OfferStepSchema],
  terms: [OfferTermSchema],
}, {
  timestamps: { createdAt: 'created_at', updatedAt: 'updated_at' }
});

export const Offer = mongoose.models.Offer || mongoose.model<IOffer>('Offer', OfferSchema);
