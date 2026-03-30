import mongoose, { Schema, Document } from 'mongoose';

export interface ICategory extends Document {
  name: string;
  emoji: string;
  sort_order: number;
}

const CategorySchema = new Schema<ICategory>({
  name: { type: String, required: true },
  emoji: { type: String, default: '📌' },
  sort_order: { type: Number, default: 0 },
});

export const Category = mongoose.models.Category || mongoose.model<ICategory>('Category', CategorySchema);
