import { NextResponse } from 'next/server';
import connectDB from '@/lib/db';
import { Offer } from '@/lib/models/Offer';

export async function GET() {
  try {
    await connectDB();
    
    const now = new Date();
    
    const result = await Offer.aggregate([
      { $match: { status: 'active' } },
      {
        $group: {
          _id: null,
          total_offers: { $sum: 1 },
          total_claimed: { $sum: '$claimed_count' },
          active_offers: {
            $sum: { $cond: [{ $gte: ['$expiry_date', now] }, 1, 0] }
          },
          max_cashback: { $max: '$max_cashback' }
        }
      }
    ]);
    
    const stats = result[0] || {
      total_offers: 0,
      total_claimed: 0,
      active_offers: 0,
      max_cashback: 0
    };
    
    return NextResponse.json({
      total_claimed: stats.total_claimed,
      active_offers: stats.active_offers,
      max_cashback: stats.max_cashback
    });
  } catch (error) {
    console.error('Error fetching stats:', error);
    return NextResponse.json({ error: 'Failed to fetch stats' }, { status: 500 });
  }
}
