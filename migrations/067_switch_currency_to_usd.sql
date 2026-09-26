-- Migration 067: the platform prices everything in US dollars.
--
-- `payment_transactions.currency` was created with a default of 'EGP' (migration 039) because the
-- EasyKash gateway was wired up first. No payment gateway is actually in use on this platform, so
-- nothing was ever really charged in Egyptian pounds and the label was only ever misleading.
--
-- This changes the default for new rows AND relabels the existing ones, because leaving a mix of
-- 'EGP' and 'USD' rows behind would make the dashboard's revenue totals sum two different
-- currencies into one number. If any row here does represent a real Egyptian-pound charge, convert
-- its `amount` before running this - the label change on its own would overstate that row.

ALTER TABLE `payment_transactions`
    MODIFY COLUMN `currency` VARCHAR(10) NOT NULL DEFAULT 'USD';

UPDATE `payment_transactions` SET `currency` = 'USD' WHERE `currency` <> 'USD';
