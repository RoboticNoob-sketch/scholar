-- Removes pending vouchers that put a scholar in more than one batch of the same program.
-- The scholar keeps the voucher in the earliest batch (or any claimed one). Claimed vouchers are never touched.
-- Preview first: change DELETE v to SELECT v.* and run it.
DELETE v FROM claim_vouchers v
JOIN distribution_batches b ON b.id = v.batch_id
WHERE v.status = 'pending'
  AND EXISTS (
      SELECT 1 FROM (
          SELECT v2.id, v2.scholar_id, b2.program_id, b2.id AS batch_id, v2.status
          FROM claim_vouchers v2
          JOIN distribution_batches b2 ON b2.id = v2.batch_id
          WHERE v2.status IN ('pending', 'claimed')
      ) keep
      WHERE keep.scholar_id = v.scholar_id
        AND keep.program_id = b.program_id
        AND keep.id <> v.id
        AND (keep.status = 'claimed' OR keep.batch_id < v.batch_id)
  );
