import { NextRequest, NextResponse } from 'next/server';
import connectDB from '@/lib/db';
import { Offer } from '@/lib/models/Offer';

export async function GET(request: NextRequest) {
  try {
    await connectDB();
    
    const { searchParams } = new URL(request.url);
    const category = searchParams.get('category') || 'All';
    const sort = searchParams.get('sort') || 'newest';
    const search = searchParams.get('search') || '';
    
    let query: Record<string, unknown> = { status: 'active' };
    
    if (category !== 'All') {
      query.category = category;
    }
    
    if (search) {
      query.$or = [
        { title: { $regex: search, $options: 'i' } },
        { brand_name: { $regex: search, $options: 'i' } },
        { description: { $regex: search, $options: 'i' } },
        { category: { $regex: search, $options: 'i' } },
      ];
    }
    
    let sortOption: Record<string, 1 | -1> = { created_at: -1 };
    
    switch (sort) {
      case 'popular':
        sortOption = { claimed_count: -1 };
        break;
      case 'expiry':
        sortOption = { expiry_date: 1 };
        break;
      case 'cashback':
        sortOption = { max_cashback: -1 };
        break;
      default:
        sortOption = { is_featured: -1, created_at: -1 };
    }
    
    const offers = await Offer.find(query).sort(sortOption).lean();
    
    const transformedOffers = offers.map(offer => ({
      ...offer,
      _id: offer._id.toString(),
    }));
    
    return NextResponse.json(transformedOffers);
  } catch (error) {
    console.error('Error fetching offers:', error);
    return NextResponse.json({ error: 'Failed to fetch offers' }, { status: 500 });
  }
}

export async function POST(request: NextRequest) {
  try {
    await connectDB();
    const body = await request.json();
    
    const offer = new Offer(body);
    await offer.save();
    
    return NextResponse.json({ success: true, offer: { ...offer.toObject(), _id: offer._id.toString() } }, { status: 201 });
  } catch (error) {
    console.error('Error creating offer:', error);
    return NextResponse.json({ error: 'Failed to create offer' }, { status: 500 });
  }
}
