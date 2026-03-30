import { NextRequest, NextResponse } from 'next/server';
import connectDB from '@/lib/db';
import { Banner } from '@/lib/models/Banner';

export async function GET() {
  try {
    await connectDB();
    const banners = await Banner.find({ status: 'active' }).sort({ sort_order: 1 }).lean();
    
    return NextResponse.json(banners.map(banner => ({
      ...banner,
      _id: banner._id.toString(),
    })));
  } catch (error) {
    console.error('Error fetching banners:', error);
    return NextResponse.json({ error: 'Failed to fetch banners' }, { status: 500 });
  }
}

export async function POST(request: NextRequest) {
  try {
    await connectDB();
    const body = await request.json();
    
    const banner = new Banner(body);
    await banner.save();
    
    return NextResponse.json({ success: true, banner: { ...banner.toObject(), _id: banner._id.toString() } }, { status: 201 });
  } catch (error) {
    console.error('Error creating banner:', error);
    return NextResponse.json({ error: 'Failed to create banner' }, { status: 500 });
  }
}

export async function PUT(request: NextRequest) {
  try {
    await connectDB();
    const body = await request.json();
    const { _id, ...updateData } = body;
    
    const banner = await Banner.findByIdAndUpdate(
      _id,
      updateData,
      { new: true }
    );
    
    if (!banner) {
      return NextResponse.json({ error: 'Banner not found' }, { status: 404 });
    }
    
    return NextResponse.json({ success: true, banner: { ...banner.toObject(), _id: banner._id.toString() } });
  } catch (error) {
    console.error('Error updating banner:', error);
    return NextResponse.json({ error: 'Failed to update banner' }, { status: 500 });
  }
}

export async function DELETE(request: NextRequest) {
  try {
    await connectDB();
    const { searchParams } = new URL(request.url);
    const id = searchParams.get('id');
    
    if (!id) {
      return NextResponse.json({ error: 'Banner ID required' }, { status: 400 });
    }
    
    const banner = await Banner.findByIdAndDelete(id);
    
    if (!banner) {
      return NextResponse.json({ error: 'Banner not found' }, { status: 404 });
    }
    
    return NextResponse.json({ success: true });
  } catch (error) {
    console.error('Error deleting banner:', error);
    return NextResponse.json({ error: 'Failed to delete banner' }, { status: 500 });
  }
}
