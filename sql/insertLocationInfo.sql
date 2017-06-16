INSERT INTO locations_info (locID, upcID)
SELECT 
		l.id AS locID, 
		u.id AS upcID
	FROM locations l
	JOIN inventory_cartons ca ON ca.locID = l.id
	JOIN inventory_batches b ON b.id = ca.batchID
	JOIN upcs u ON u.id = b.upcID
	WHERE l.isMezzanine
	AND NOT EXISTS (
		SELECT 	l1.locID, l1.upcID
		FROM locations_info l1 
		WHERE l1.locID = ca.locID AND l1.upcID = b.upcID
	)	
GROUP BY l.id, u.id 
	