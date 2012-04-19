<?php


interface BuyableModel {



	//GROUPS AND SIBLINGS

	/**
	 * Returns the direct parent (group) for the product.
	 *
	 * @return Null | DataObject(ProductGroup)
	 **/
	function MainParentGroup();

	/**
	 * Returns Buybales in the same group
	 * @return Null | DataObjectSet
	 **/
	function Siblings();




	//IMAGES

	/**
	 * returns a link to the standard image
	 * @return String
	 */
	public function DefaultImageLink();

	/**
	 * returns a product image for use in templates
	 * e.g. $DummyImage.Width();
	 * @return Product_Image
	 */
	public function DummyImage();




	// VERSIONING

	/**
	 * View a speficic version
	 */
	function viewversion($request);




	//ORDER ITEM

	/**
	 * returns the order item associated with the buyable.
	 * ALWAYS returns one, even if there is none in the cart.
	 * Does not write to database.
	 * @return OrderItem (no kidding)
	 **/
	public function OrderItem();

	/**
	 *
	 * @var String
	 */
	//protected $defaultClassNameForOrderItem;

	/**
	 * you can overwrite this function in your buyable items (such as Product)
	 * @return String
	 **/
	public function classNameForOrderItem();

	/**
	 * You can set an alternative class name for order item using this method
	 * @param String $ClassName
	 **/
	public function setAlternativeClassNameForOrderItem($className);

	/**
	 * This is used when you add a product to your cart
	 * if you set it to 1 then you can add 0.1 product to cart.
	 * If you set it to -1 then you can add 10, 20, 30, etc.. products to cart.
	 *
	 * @return Int
	 **/
	function QuantityDecimals();

	/**
	 * Has it been sold?
	 * @return Boolean
	 */
	function HasBeenSold();




	//LINKS

	function Link($action = null);

	/**
	 * passing on shopping cart links ...is this necessary?? ...why not just pass the cart?
	 * @return String
	 */
	function AddLink();

	/**
	 * link use to add (one) to cart
	 *@return String
	 */
	function IncrementLink();

	/**
	 * Link used to remove one from cart
	 * we can do this, because by default remove link removes one
	 * @return String
	 */
	function DecrementLink();

	/**
	 * remove one buyable's orderitem from cart
	 * @return String (Link)
	 */
	function RemoveLink();

	/**
	 * remove all of this buyable's orderitem from cart
	 * @return String (Link)
	 */
	function RemoveAllLink();

	/**
	 * remove all of this buyable's orderitem from cart and go through to this buyble to add alternative selection.
	 * @return String (Link)
	 */
	function RemoveAllAndEditLink();

	/**
	 * set new specific new quantity for buyable's orderitem
	 * @param double
	 * @return String (Link)
	 */
	function SetSpecificQuantityItemLink($quantity);





	//TEMPLATE STUFF

	/**
	 *
	 * @return boolean
	 */
	function IsInCart();

	/**
	 * returns the instance of EcommerceConfigAjax for use in templates.
	 * In templates, it is used like this:
	 * $EcommerceConfigAjax.TableID
	 *
	 * @return EcommerceConfigAjax
	 **/
	public function AJAXDefinitions();

	/**
	 * returns the instance of EcommerceDBConfig
	 *
	 * @return EcommerceDBConfig
	 **/
	public function EcomConfig();

	/**
	 * Is it a variation?
	 * @return Boolean
	 */
	function IsProductVariation();


	/**
	 * Products have a standard price, but for specific situations they have a calculated price.
	 * The Price can be changed for specific member discounts, a different currency, etc...
	 * @todo: return as Money
	 * @return Currency (casted variable)
	 */
	function CalculatedPrice();

	/**
	 * Products have a standard price, but for specific situations they have a calculated price.
	 * The Price can be changed for specific member discounts, a different currency, etc...
	 * @todo: return as Money
	 * @return Currency (casted variable)
	 */
	function DisplayPrice();




	//CRUD SETTINGS

	/**
	 * Is the product for sale?
	 * @return Boolean
	 */
	function canPurchase($member = null);



}
