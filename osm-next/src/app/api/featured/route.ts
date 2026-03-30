import { NextResponse } from 'next/server';
import connectDB from '@/lib/db';
import { Offer } from '@/lib/models/Offer';

export async function GET() {
  try {
    await connectDB();
    
    const offers = await Offer.find({ 
      status: 'active', 
      is_featured: true 
    })
    .sort({ is_popular: -1, created_at: -1 })
    .limit(5)
    .lean();
    
    return NextResponse.json(offers.map(offer => ({
      ...offer,
      _id: offer._id.toString(),
    })));
  } catch (error) {
    console.error('Error fetching featured offers:', error);
    return NextResponse.json({ error: 'Failed to fetch featured offers' }, { status: 500 });
  }
}
