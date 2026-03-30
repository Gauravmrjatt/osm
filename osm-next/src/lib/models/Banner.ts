import mongoose, { Schema, Document } from 'mongoose';

export interface IBanner extends Document {
  image_url: string;
  link_url?: string;
  sort_order: number;
  status: 'active' | 'inactive';
  created_at: Date;
}

const BannerSchema = new Schema<IBanner>({
  image_url: { type: String, required: true },
  link_url: { type: String, default: '' },
  sort_order: { type: Number, default: 0 },
  status: { type: String, enum: ['active', 'inactive'], default: 'active' },
  created_at: { type: Date, default: Date.now },
});

export const Banner = mongoose.models.Banner || mongoose.model<IBanner>('Banner', BannerSchema);
