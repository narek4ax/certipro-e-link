CREATE PROCEDURE spPriceCalculation(
	IN p_ItemCode VARCHAR(30),
	IN p_ARDivisionNo VARCHAR(2),
	IN p_CustomerNo VARCHAR(20),
	IN p_PriceLevel VARCHAR(1),
	IN p_Quantity DECIMAL(16,6),
	IN p_Date DATETIME,
	OUT p_UnitPrice DECIMAL(16,6)
	)
BEGIN
	DECLARE cLoop smallint;
	DECLARE useBreak smallint;
	DECLARE cPriceType smallint;
	DECLARE cPriceCode varchar(4);
	DECLARE cPricingMethod varchar(1);
	DECLARE cBreakQuantity1 int;
	DECLARE cBreakQuantity2	int;
	DECLARE cBreakQuantity3	int;
	DECLARE cBreakQuantity4	int;
	DECLARE cBreakQuantity5	int;
	DECLARE cDiscountMarkup1 decimal(16,6);
	DECLARE cDiscountMarkup2 decimal(16,6);
	DECLARE cDiscountMarkup3 decimal(16,6);
	DECLARE cDiscountMarkup4 decimal(16,6);
	DECLARE cDiscountMarkup5 decimal(16,6);
	DECLARE cStandardUnitPrice decimal(16,6);
	DECLARE cStandardUnitCost decimal(16,6);
	DECLARE cLastTotalUnitCost decimal(16,6);
	DECLARE cSalesPromotionPrice decimal(16,6);
	DECLARE cSalesPromotionDiscountPercent decimal(12,3);
	DECLARE cSalesPromotionCode varchar(10);
	DECLARE cSaleStartingDate date;
	DECLARE cSaleEndingDate date;
	DECLARE cSaleMethod varchar(1);
	DECLARE cPricingRate decimal(16,6);
	DECLARE cSalePrice decimal(16,6);

	SELECT	price_code,standard_unit_price,standard_unit_cost,last_total_unit_cost,sales_promotion_price,sales_promotion_discount_percent,sales_promotion_code,sale_starting_date,sale_ending_date,sale_method
	INTO cPriceCode,cStandardUnitPrice , cStandardUnitCost ,cLastTotalUnitCost,cSalesPromotionPrice,cSalesPromotionDiscountPercent,cSalesPromotionCode,cSaleStartingDate,cSaleEndingDate,cSaleMethod
	FROM	cps_items
	WHERE	item_code = p_ItemCode;

	IF p_PriceLevel IS NULL THEN
		SET p_PriceLevel='';
	END IF;

	SELECT	1,pricing_method,break_quantity1,break_quantity2,break_quantity3,break_quantity4,break_quantity5, discount_markup1, discount_markup2, discount_markup3, discount_markup4, discount_markup5
	INTO cPriceType ,cPricingMethod,cBreakQuantity1,cBreakQuantity2,cBreakQuantity3,cBreakQuantity4,cBreakQuantity5,cDiscountMarkup1,cDiscountMarkup2,cDiscountMarkup3,cDiscountMarkup4,cDiscountMarkup5
	FROM	cps_price_codes
	WHERE	price_code_record = '2' AND price_code='' AND item_code = p_ItemCode AND customer_pricelevel='' AND ar_division_no = p_ARDivisionNo AND  customer_no = p_CustomerNo	;

	IF cPriceType IS NULL THEN BEGIN

		SELECT	CASE WHEN LTRIM(RTRIM(p_PriceLevel)) = '' THEN 3 ELSE 2 END,pricing_method,break_quantity1,break_quantity2,break_quantity3,break_quantity4,break_quantity5, discount_markup1, discount_markup2, discount_markup3, discount_markup4, discount_markup5
		INTO cPriceType ,cPricingMethod,cBreakQuantity1,cBreakQuantity2,cBreakQuantity3,cBreakQuantity4,cBreakQuantity5,cDiscountMarkup1,cDiscountMarkup2,cDiscountMarkup3,cDiscountMarkup4,cDiscountMarkup5
		FROM	cps_price_codes
		WHERE	price_code_record = '1' AND price_code='' AND item_code = p_ItemCode AND customer_pricelevel=p_PriceLevel AND ar_division_no = '' AND  customer_no = ''	;

	END; END IF;

	IF cPriceType IS NULL AND LTRIM(RTRIM(p_PriceLevel)) <> '' THEN BEGIN

		SELECT	4,pricing_method,break_quantity1,break_quantity2,break_quantity3,break_quantity4,break_quantity5, discount_markup1, discount_markup2, discount_markup3, discount_markup4, discount_markup5
		INTO cPriceType ,cPricingMethod,cBreakQuantity1,cBreakQuantity2,cBreakQuantity3,cBreakQuantity4,cBreakQuantity5,cDiscountMarkup1,cDiscountMarkup2,cDiscountMarkup3,cDiscountMarkup4,cDiscountMarkup5
		FROM	cps_price_codes
		WHERE	price_code_record = '0' AND price_code = cPriceCode AND item_code = '' AND customer_pricelevel=p_PriceLevel  AND ar_division_no = '' AND  customer_no = ''	;

	END; END IF;

	IF cPriceType IS NULL   THEN BEGIN

		SELECT	5,pricing_method,break_quantity1,break_quantity2,break_quantity3,break_quantity4,break_quantity5, discount_markup1, discount_markup2, discount_markup3, discount_markup4, discount_markup5
		INTO cPriceType ,cPricingMethod,cBreakQuantity1,cBreakQuantity2,cBreakQuantity3,cBreakQuantity4,cBreakQuantity5,cDiscountMarkup1,cDiscountMarkup2,cDiscountMarkup3,cDiscountMarkup4,cDiscountMarkup5
		FROM	cps_price_codes
		WHERE	price_code_record = '0' AND price_code = cPriceCode AND item_code = '' AND customer_pricelevel=''  AND ar_division_no = '' AND  customer_no = ''	;

	END; END IF;
	IF cPriceType IS NULL   THEN BEGIN

		SET		cPriceType = 6;
		SET		cPriceCode = '3';
		SET		cPricingMethod = 'S';
		SET		cBreakQuantity1 = 99999999;

	END; END IF;

	SET useBreak = 5;

	IF cBreakQuantity5 = 0 THEN
		SET useBreak = 4;
	ELSE
		IF p_Quantity <= cBreakQuantity5 THEN
			SET useBreak = 5;
		END IF;
	END IF;

	IF cBreakQuantity4 = 0 THEN
		SET useBreak = 3;
	ELSE
		IF p_Quantity <= cBreakQuantity4 THEN
			SET useBreak = 4;
		END IF;
	END IF;

	IF cBreakQuantity3 = 0 THEN
		SET useBreak = 2;
	ELSE
		IF p_Quantity <= cBreakQuantity3 THEN
			SET useBreak = 3;
		END IF;
	END IF;

	IF cBreakQuantity2 = 0 THEN
		SET useBreak = 1;
	ELSE
		IF p_Quantity <= cBreakQuantity2 THEN
			SET useBreak = 2;
		END IF;
	END IF;


	IF cBreakQuantity1 = 0 THEN
		SET useBreak = 1;
	ELSE
		IF p_Quantity <= cBreakQuantity1 THEN
			SET useBreak = 1;
		END IF;
	END IF;

	IF cPricingMethod = 'S' THEN
		SET cDiscountMarkup1 = cStandardUnitPrice;
	END IF;

	SET cPricingRate =
	CASE useBreak
		WHEN 1 THEN	cDiscountMarkup1
		WHEN 2 THEN	cDiscountMarkup2
		WHEN 3 THEN	cDiscountMarkup3
		WHEN 4 THEN	cDiscountMarkup4
		WHEN 5 THEN	cDiscountMarkup5
	END;

	SET p_UnitPrice = cStandardUnitPrice;
	IF cPricingMethod = 'C' THEN BEGIN
		SET p_UnitPrice = cStandardUnitCost + cPricingRate;
		IF cStandardUnitCost = 0  THEN
			SET p_UnitPrice = cLastTotalUnitCost + cPricingRate;
		END IF;
	END; END IF;

	IF cPricingMethod = 'P' THEN
		SET p_UnitPrice = cStandardUnitPrice - cPricingRate;
	END IF;

	IF cPricingMethod = 'M' THEN BEGIN
		SET p_UnitPrice = cStandardUnitCost + cStandardUnitCost * cPricingRate / 100;
		IF cStandardUnitCost = 0 THEN
			SET p_UnitPrice = cLastTotalUnitCost + cLastTotalUnitCost * cPricingRate / 100;
		END IF;
	END; END IF;

	IF cPricingMethod = 'D' THEN
		SET p_UnitPrice = cStandardUnitPrice - cStandardUnitPrice * cPricingRate / 100;
	END IF;

	IF cPricingMethod = 'O' or cPricingMethod = 'S' THEN
		SET p_UnitPrice = cPricingRate;
	END IF;

	SET cSalePrice = 0;
	IF cSaleMethod = 'P' THEN
		SET cSalePrice = cSalesPromotionPrice;
	ELSE
		IF cSaleMethod = 'D' THEN
			SET cSalePrice = cStandardUnitPrice - cStandardUnitPrice * cSalesPromotionDiscountPercent / 100;
		END IF;
	END IF;

	IF NOT cSalesPromotionCode is null AND p_Date >= cSaleStartingDate AND p_Date <= cSaleEndingDate AND cSalePrice < p_UnitPrice THEN
		SET p_UnitPrice = cSalePrice;
		SET cBreakQuantity1 = 99999999;
		SET cDiscountMarkup1 = 0;
	END IF;

	select p_UnitPrice,cBreakQuantity1,cBreakQuantity2,cBreakQuantity3,cBreakQuantity4,cBreakQuantity5,cDiscountMarkup1,cDiscountMarkup2,cDiscountMarkup3,cDiscountMarkup4,cDiscountMarkup5;

END