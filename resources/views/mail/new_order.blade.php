<x-mail::message>
    <h1 style="text-align: center;font-size: 24px">
        Congratulation! you have new order
    </h1>
    <x-mail::button :url="$order->id">
        View order details
    </x-mail::button>
    <h3 style="font-size: 20px;margin-bottom: 15px" >
        Order Summery
    </h3>
    <x-mail::table>
        <table>
            <tbody>
            <tr>
                <td>Order #</td>
                <td>{{$order->id}}</td>
            </tr>
            <tr>
                <td>Order Date</td>
                <td>{{$order->created_at}}</td>
            </tr>
            <tr>
                <td>Order Total</td>
                <td>{{\Illuminate\Support\Number::currency($order->total_price)}}</td>
            </tr>
            <tr>
                <td>Payment Processing  Fee</td>
                <td>{{\Illuminate\Support\Number::currency($order->online_payment_commission ? :0)}}</td>
            </tr>
            <tr>
                <td>Platform Fee</td>
                <td>{{\Illuminate\Support\Number::currency($order->website_commission ? :0)}}</td>
            </tr>
            <tr>
                <td>Your Earnings </td>
                <td>{{\Illuminate\Support\Number::currency($order->vendor_subtotal ? :0)}}</td>
            </tr>
            <hr>
            <x-mail::table>
                <table>
                    <thead>
                    <tr>
                        <th>Item</th>
                        <th>Quantity</th>
                        <th>Price</th>
                    </tr>
                    </thead>
                    <tbody>
                        @foreach($order->orderItems as $orderItem)
                            <tr>
                                <td>
                                    <table>
                                       <tbody>
                                       <tr>
                                           <td padding="5" style="padding: 5px" >
                                               <img src="{{$orderItem->product->getImageForOptions($orderItem->variation_type_option_ids)}}" style="min-width: 60px;max-width: 60px" alt="">
                                           </td>
                                           <td style="font-size: 13px;padding: 5px">
                                               {{$orderItem->product->title}}
                                           </td>
                                       </tr>
                                       </tbody>
                                    </table>
                                </td>
                                <td>
                                    {{$orderItem->quantity}}
                                </td>
                                <td>
                                    {{\Illuminate\Support\Number::currency($orderItem->price)}}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-mail::table>
            </tbody>
        </table>
    </x-mail::table>
    <x-mail::panel>
        Thank You For having business with us.
    </x-mail::panel>
    Thanks,</br>
    {{config('app.name')}}
</x-mail::message>
