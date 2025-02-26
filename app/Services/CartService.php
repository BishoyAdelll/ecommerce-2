<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\VariationType;
use App\Models\VariationTypeOption;
use http\Cookie;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use phpDocumentor\Reflection\Types\Self_;
use function Laravel\Prompts\select;

class CartService
{
    private ?array $cashedCartItems = null;
    protected const COOKIE_NAME = 'cartItems';
    protected const COOKIE_LIFETIME = 60*24*365;
    public function addItemToCart(Product $product, int $quantity = 1, $optionIds=null)
    {
            if($optionIds === null){
                $optionIds = $product->variationTypes
                    ->mapWithKeys(fn (VariationType $type) =>[
                            $type->id =>$type->options[0]?->id
                    ])->toArray();
            }
            $price =$product->getPriceForOptions($optionIds);

            if( Auth::check() ) {
                $this->saveItemToDatabase($product->id, $quantity, $price, $optionIds);
            }else{
                $this->saveItemCookies($product->id,$quantity, $price, $optionIds);
            }
    }
    public function updateItemQuantity(int $productId,int $quantity,$optionIds = null)
    {
        if( Auth::check() ) {
            $this->updateItemQuantityInDatabase($productId, $quantity, $optionIds);
        }else{
            $this->updateItemQuantityInCookies($productId,$quantity, $optionIds);
        }
    }
    public function removeItemFromCart(int $productId,$optionIds=null)
    {
        if( Auth::check() ) {
            $this->removeItemFromDatabase($productId, $optionIds);
        }else{
            $this->removeItemFromCookies($productId, $optionIds);
        }
    }

    public function getCartItems():array
    {
        try{
            if($this->cashedCartItems === null){
                if(Auth::check()){
                    $cartItems = $this->getCartItemsFromDatabase();
                }else{
                    $cartItems = $this->getCartItemsFromCookies();
                }

                $productIds = collect($cartItems)->map(fn($item)=>$item['product_id']);
                $products = Product::whereIn('id',$productIds)
                    ->with('user.vendor')
                     ->forWebsites()
                    ->get()
                    ->keyBy('id');
                $cartItemData = [];
                foreach ($cartItems as $key=>$cartItem){
                    $product = data_get($products,$cartItem['product_id']);
                    if(!$product) continue;

                    $optionInfo = [];
                    $options = VariationTypeOption::with('variationType')
                        ->whereIn('id',$cartItem['option_ids'])
                        ->get()
                        ->keyBy('id');

                    $imageUrl =null;
                    foreach ($cartItem['option_ids'] as $option_id){

                        $option = data_get($options,$option_id);

                        if(!$imageUrl)
                        {
                            $imageUrl = $option->getFirstMediaUrl('images','small');
                        }
                        $optionInfo[] = [
                            'id' => $option->id,
                            'name' => $option->name,
                            'type' =>[
                                'id' => $option->variationType->id,
                                'name' => $option->variationType->name,
                            ]
                        ];
                    }
                    $cartItemData[] = [
                        'id' => $cartItem['id'],
                        'product_id' => $product->id,
                        'title' =>$product->title,
                        'slug' => $product->slug,
                        'price' => $cartItem['price'],
                        'quantity' => $cartItem['quantity'],
                        'option_ids' => $cartItem['option_ids'],
                        'options' => $optionInfo,
                        'image' => $imageUrl ?:  $product->getFirstMediaUrl('images','small'),
                        'user' =>[
                            'id' => $product->created_by,
                            'name' => $product->user->vendor->store_name,

                        ]
                    ];

                }
                $this->cashedCartItems = $cartItemData;
            }
            return $this->cashedCartItems;
        }catch (\Exception $e){
//            throw  $e;
            Log::error($e->getMessage() . PHP_EOL . $e->getTraceAsString());
        }
        return [];
    }

    public function getTotalQuantity(): int
    {
        $totalQuantity = 0;
        foreach ($this->getCartItems() as $item){
            $totalQuantity += $item['quantity'];
        }
        return $totalQuantity;

    }
    public function getTotalPrice(): float
    {
        $total = 0;
        foreach ($this->getCartItems() as $item){
            $total +=   $item['quantity']* $item['price'];
        }
        return $total;
    }
    protected function updateItemQuantityInDatabase(int $productId,int $quantity,array $optionIds ): void
    {
        $userId  =Auth::id();
        $cartItem = CartItem::where('user_id',$userId)
            ->where('product_id',$productId)
            ->where('variation_type_option_ids',json_encode($optionIds))
            ->first();
        if($cartItem){
            $cartItem->update([
                'quantity' => $quantity
            ]);
        }
    }
    protected function updateItemQuantityInCookies(int $productId,int $quantity,array $optionIds): void
    {
        $cartItems = $this->getCartItemsFromCookies();
        ksort($optionIds);
        $itemKey = $productId . '_' . json_encode($optionIds);
        if(isset($cartItems[$itemKey])){
            $cartItems[$itemKey]['quantity'] += $quantity;
        }
        \Illuminate\Support\Facades\Cookie::queue(Self::COOKIE_NAME,json_encode($cartItems),self::COOKIE_LIFETIME);
    }
    protected function saveItemToDatabase(int $product,int $quantity, $price,array $optionIds): void
    {
        $userId  =Auth::id();
        ksort($optionIds);
        $cartItem = CartItem::where('user_id',$userId)
            ->where('product_id',$product)
            ->where('variation_type_option_ids',json_encode($optionIds))
            ->first();
        if($cartItem){
            $cartItem->update([
                'quantity' => DB::raw('quantity + '.$quantity),
            ]);
        }else{
            CartItem::create([
                'user_id' => $userId,
                'product_id' => $product,
                'variation_type_option_ids' => json_encode($optionIds),
                'quantity' => $quantity,
                'price' => $price,
            ]);
        }
    }
    protected function saveItemCookies(int $productId,int $quantity, $price,array $optionIds): void
    {
//        dd($cartItems,$productId);
        $cartItems = $this->getCartItemsFromCookies();
//        dd($cartItems,$productId,$price,$quantity,$optionIds);
        ksort($optionIds);
        $itemKey = $productId . '_' . json_encode($optionIds);
        if(isset($cartItems[$itemKey])){
            $cartItems[$itemKey]['quantity'] += $quantity;
            $cartItems[$itemKey]['price'] = $price;
        }else{
            $cartItems[$itemKey] =[
                'id' => Str::uuid(),
                'product_id' => $productId,
                'quantity' => $quantity,
                'price' => $price,
                'option_ids' => $optionIds,
            ];
        }
        \Illuminate\Support\Facades\Cookie::queue(self::COOKIE_NAME,json_encode($cartItems),self::COOKIE_LIFETIME);
    }

    protected function removeItemFromDatabase(int $product,array $optionIds){
        $userId  =Auth::id();
        $cartItems = $this->getCartItemsFromCookies();
        ksort($optionIds);
        CartItem::where('user_id',$userId)
            ->where('product_id',$product)
            ->where('variation_type_option_ids',json_encode($optionIds))
            ->delete();

    }
    protected function removeItemFromCookies(int $productId,$optionIds=null){
        $cartItems = $this->getCartItemsFromCookies();
        ksort($optionIds);
        $cartKey = $productId . '_' . json_encode($optionIds);
        unset($cartItems[$cartKey]);
        \Illuminate\Support\Facades\Cookie::queue(self::COOKIE_NAME,json_encode($cartItems),self::COOKIE_LIFETIME);
    }

    protected function getCartItemsFromDatabase():array
    {
        $userId  =Auth::id();
        $cartItems = CartItem::where('user_id',$userId)
        ->get()
        ->map(function ($cartItem){
            return[
                'id' => $cartItem->id,
                'product_id' =>$cartItem->product_id,
                'quantity' => $cartItem->quantity,
                'price' => $cartItem->price,
                'option_ids' => $cartItem->variation_type_option_id,
            ];
        })->toArray();
        return $cartItems;
    }
    protected function getCartItemsFromCookies():array
    {
        $cartItems = json_decode(\Illuminate\Support\Facades\Cookie::get(Self::COOKIE_NAME,'[]'),true);
        return $cartItems;
    }
}
