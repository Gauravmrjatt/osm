import { NextRequest, NextResponse } from 'next/server';
import connectDB from '@/lib/db';
import { Category } from '@/lib/models/Category';

export async function GET() {
  try {
    await connectDB();
    const categories = await Category.find().sort({ sort_order: 1 }).lean();
    
    return NextResponse.json(categories.map(cat => ({
      ...cat,
      _id: cat._id.toString(),
    })));
  } catch (error) {
    console.error('Error fetching categories:', error);
    return NextResponse.json({ error: 'Failed to fetch categories' }, { status: 500 });
  }
}

export async function POST(request: NextRequest) {
  try {
    await connectDB();
    const body = await request.json();
    
    const category = new Category(body);
    await category.save();
    
    return NextResponse.json({ success: true, category: { ...category.toObject(), _id: category._id.toString() } }, { status: 201 });
  } catch (error) {
    console.error('Error creating category:', error);
    return NextResponse.json({ error: 'Failed to create category' }, { status: 500 });
  }
}

export async function PUT(request: NextRequest) {
  try {
    await connectDB();
    const body = await request.json();
    const { _id, ...updateData } = body;
    
    const category = await Category.findByIdAndUpdate(
      _id,
      updateData,
      { new: true }
    );
    
    if (!category) {
      return NextResponse.json({ error: 'Category not found' }, { status: 404 });
    }
    
    return NextResponse.json({ success: true, category: { ...category.toObject(), _id: category._id.toString() } });
  } catch (error) {
    console.error('Error updating category:', error);
    return NextResponse.json({ error: 'Failed to update category' }, { status: 500 });
  }
}

export async function DELETE(request: NextRequest) {
  try {
    await connectDB();
    const { searchParams } = new URL(request.url);
    const id = searchParams.get('id');
    
    if (!id) {
      return NextResponse.json({ error: 'Category ID required' }, { status: 400 });
    }
    
    const category = await Category.findByIdAndDelete(id);
    
    if (!category) {
      return NextResponse.json({ error: 'Category not found' }, { status: 404 });
    }
    
    return NextResponse.json({ success: true });
  } catch (error) {
    console.error('Error deleting category:', error);
    return NextResponse.json({ error: 'Failed to delete category' }, { status: 500 });
  }
}
