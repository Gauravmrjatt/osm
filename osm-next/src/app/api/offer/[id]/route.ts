import { NextRequest, NextResponse } from 'next/server';
import connectDB from '@/lib/db';
import { Offer } from '@/lib/models/Offer';
import { deleteFromCloudinary } from '@/lib/cloudinary';
import mongoose from 'mongoose';

export async function GET(
  request: NextRequest,
  { params }: { params: Promise<{ id: string }> }
) {
  try {
    await connectDB();
    const { id } = await params;
    
    if (!mongoose.Types.ObjectId.isValid(id)) {
      return NextResponse.json({ error: 'Invalid offer ID' }, { status: 400 });
    }
    
    const offer = await Offer.findById(id).lean();
    
    if (!offer) {
      return NextResponse.json({ error: 'Offer not found' }, { status: 404 });
    }
    
    return NextResponse.json({
      ...offer,
      _id: offer._id.toString(),
    });
  } catch (error) {
    console.error('Error fetching offer:', error);
    return NextResponse.json({ error: 'Failed to fetch offer' }, { status: 500 });
  }
}

export async function PUT(
  request: NextRequest,
  { params }: { params: Promise<{ id: string }> }
) {
  try {
    await connectDB();
    const { id } = await params;
    const body = await request.json();
    
    const offer = await Offer.findByIdAndUpdate(
      id,
      { ...body, updated_at: new Date() },
      { new: true }
    );
    
    if (!offer) {
      return NextResponse.json({ error: 'Offer not found' }, { status: 404 });
    }
    
    return NextResponse.json({ success: true, offer: { ...offer.toObject(), _id: offer._id.toString() } });
  } catch (error) {
    console.error('Error updating offer:', error);
    return NextResponse.json({ error: 'Failed to update offer' }, { status: 500 });
  }
}

export async function DELETE(
  request: NextRequest,
  { params }: { params: Promise<{ id: string }> }
) {
  try {
    await connectDB();
    const { id } = await params;
    
    const offer = await Offer.findById(id);
    
    if (!offer) {
      return NextResponse.json({ error: 'Offer not found' }, { status: 404 });
    }
    
    // Delete video from Cloudinary if it's from cloudinary
    if (offer.video_source === 'cloudinary' && offer.video_public_id) {
      await deleteFromCloudinary(offer.video_public_id);
    }
    
    await Offer.findByIdAndDelete(id);
    
    return NextResponse.json({ success: true });
  } catch (error) {
    console.error('Error deleting offer:', error);
    return NextResponse.json({ error: 'Failed to delete offer' }, { status: 500 });
  }
}
