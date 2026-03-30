import { NextResponse } from 'next/server';
import connectDB from '@/lib/db';
import { Offer } from '@/lib/models/Offer';

export async function GET() {
  try {
    await connectDB();
    
    const offers = await Offer.find().sort({ created_at: -1 }).lean();
    
    return NextResponse.json(offers.map(offer => ({
      ...offer,
      _id: offer._id.toString(),
    })));
  } catch (error) {
    console.error('Error fetching all offers:', error);
    return NextResponse.json({ error: 'Failed to fetch offers' }, { status: 500 });
  }
}
