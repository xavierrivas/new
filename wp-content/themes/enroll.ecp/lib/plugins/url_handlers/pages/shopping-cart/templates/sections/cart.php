<?php if($params != null && !$params -> isEmpty()): ?>
<div class="order_cart">
	<h5>Your Cart:</h5>

	<table cellpadding="0" cellspacing="0" width="100%">
			<?php 
				$products = $params -> getAllProducts();
				
				$products_with_coupon = 0;
				$total_price = 0;
				$i = 1; 
				$dis_class = "odd";
				
				if($params -> getCoupon())
				{
					$coupon = $params -> getCoupon() -> getPrice();
				}
				$reduce = 0;
				foreach($products as $product)
				{
					$class = "odd";
					$dis_class = "even";
					if($i % 2 == 0 && $i > 0){ $class = "even"; $dis_class = "odd";}
					
					if(!is_null($product -> name)) 
					{
					?>
						<tr class="<?php echo $class;?>">
							<td>
								<?php echo $product -> taxonomy ?> - <?php echo $product -> name ?>
								<?php if( $product -> is_essay): ?>
									<span><small>x</small></span><span><small><?php echo $product -> quantity; ?></small></span>
								<?php endif; ?>
							</td>
							<td class="product_price">
									$&nbsp;<?php echo ((int)$product -> price * (int)$product -> quantity); ?>
							</td>
							<td class="remove_product"><a href="<?php echo $product -> id; ?>"></a></td>
						</tr>
					<?php 
					}
					if($params -> checkProductInCoupon($product -> id))
					{
						$products_with_coupon += $product -> quantity;
					}
					
					/*if($product -> discounted_price)
					{
						$total_price += $product -> discounted_price;
					}
					else 
					{*/
						$total_price += $product -> full_price;
					//}
					$i ++;
				}
			?>
			<?php if($params -> getCoupon()) : ?>

				<tr class="<?php echo $dis_class;?>">
						<td>Your coupon discount: </td>
						<td class="product_price">$&nbsp;<?php echo $coupon[1] * $products_with_coupon; ?></td>
						<td></td>
				</tr>
			
			<?php endif; ?>
			
	</table>
</div>
<div class="order_total">
	<span class="total">Your total:</span> 
	<span class="total_price">$ <?php echo $total_price; ?></span>
</div>
<?php else: ?>
<div class="order_cart">
	<h5>Your cart is empty</h5>
</div>
<?php endif; ?>