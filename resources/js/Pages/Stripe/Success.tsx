import {Head,Link} from "@inertiajs/react"

import {CheckCircleIcon} from "@heroicons/react/24/outline"
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import {PageProps,Order} from "@/types";
import CurrencyFormatter from "@/Components/Core/CurrencyFormatter";
function Success({orders}:PageProps<{orders:Order[]}>) {
  console.log(orders)
  return (
      <AuthenticatedLayout>
        <Head title="Payment Was Completed"/>
        <div className="w-[480px] mx-auto py-8 px-4">
          <div className="flex flex-col gap-2 items-center">
            <div className="text-6xl text-emerald-600">
              <CheckCircleIcon className={"size-24"}/>
            </div>
            <div className="text-3xl">
              Payment Was Completed
            </div>
          </div>
          <div className="my-6 text-lg">
            Thanks For your Purchase . Your payment was Completed Successfully
          </div>
          {orders.map(order => (
            <div className="bg-white dark:bg-gray-800 rounded-lg p-6 mb-4">
              <h3 className="text-3xl">Order Summery</h3>
              <div className="flex justify-between mb-2 font-bold">
                <div className="text-gray-400">
                  Seller
                </div>
                <div>
                  <Link href="#" className="hover:underline">
                    {order.vendorUser.store_name}
                  </Link>
                </div>
              </div>
              <div className="flex justify-between mb-2 ">
                <div className="text-gray-400">
                  Order Number
                </div>
                <div>
                  <Link href="#" className="hover:underline">#{order.id} </Link>
                </div>
              </div>
              <div className="flex justify-between mb-3">
                <div className="text-gray-400">
                  items
                </div>
                <div>
                  {order.orderItems.length}
                </div>
              </div>
              <div className="flex justify-between mb-3">
                <div className="text-gray-400">
                  Total
                </div>
                <div>
                  <CurrencyFormatter amount={order.total_price}/>
                </div>
              </div>
              <div className="flex justify-between mt-4">
                <Link href='#' className="btn btn-primary">
                  View Order Details
                </Link>
                <Link href={route('dashboard')} className="btn ">
                  Back To Home
                </Link>
              </div>
            </div>
          ))}
        </div>
      </AuthenticatedLayout>
  );
}

export default Success;
