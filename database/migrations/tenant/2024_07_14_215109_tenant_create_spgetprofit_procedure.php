<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class TenantCreateSpgetprofitProcedure extends Migration
{
    public function up()
    {
            $sql = "CREATE PROCEDURE spGetProfit(ESTABLISMENT_ID INTEGER, WAREHOUSE_ID INTEGER, INITIALDATE DATE, ENDDATE DATE)
                    BEGIN
                        DECLARE EXCHANGE_RATE_PER_DAY FLOAT;
                        DECLARE CURRENCY_TYPE VARCHAR(3);
                        DECLARE QTY FLOAT;
                        DECLARE PRICE FLOAT;
                        DECLARE TRANS VARCHAR(50);
                        DECLARE DATE_ISSUE DATE;
                        DECLARE MONTOSFMA FLOAT DEFAULT 0;
                        DECLARE MONTOSCOMPRAS FLOAT DEFAULT 0;
                        DECLARE MONTOSINGRESOS FLOAT DEFAULT 0;
                        DECLARE MONTOSEGRESOS FLOAT DEFAULT 0;
                        DECLARE MONTOSVENTAS FLOAT DEFAULT 0;
                        DECLARE MONTOSSFMV FLOAT DEFAULT 0;                    
                        DECLARE RESULT INTEGER;
                        DECLARE VAR_FINAL INTEGER DEFAULT 0;

    

                            /*Cursor Saldo FInal compras ingresos egresos ventas*/
                            DECLARE cursorFinalBalance CURSOR FOR 
                                   SELECT m.date_of_movement AS fecha, i.currency_type_id as moneda, m.quantity as cantidad , i.purchase_unit_price as precio_compra, 
                                   (SELECT sale_original from exchange_rates tc where tc.date = (SELECT DATE_ADD(DATE_ADD(LAST_DAY(NOW()), INTERVAL -1 DAY),INTERVAL -1 MONTH))) as tipo_cambio,  'SFMA' as transaccion
                              FROM item_movement m JOIN items i ON m.item_id = i.id 
                             WHERE m.date_of_movement BETWEEN CAST((DATE_ADD(DATE_ADD(LAST_DAY(INITIALDATE), INTERVAL 0 DAY),INTERVAL -1 MONTH) - DAY(INITIALDATE)) AS DATE)
                               AND CAST((DATE_ADD(DATE_ADD(LAST_DAY(ENDDATE), INTERVAL 0 DAY),INTERVAL -1 MONTH) - DAY(ENDDATE)) AS DATE)
                               AND i.warehouse_id = WAREHOUSE_ID
                            UNION ALL
                            SELECT c.date_of_issue as fecha, c.currency_type_id as moneda, i.quantity as cantidad, i.unit_price as precio_compra, c.exchange_rate_sale as tipo_cambio, 'COMPRAS' as transaccion
                                FROM purchases c JOIN purchase_items i ON c.id = i.purchase_id
                                WHERE C.date_of_issue BETWEEN INITIALDATE AND ENDDATE
                                 AND c.establishment_id = ESTABLISMENT_ID
                            UNION ALL 
                            SELECT c.date_of_issue as fecha, c.currency_type_id as moneda, i.total as cantidad, 1 as precio_compra, c.exchange_rate_sale as tipo_cambio, 'INGRESOS' as transaccion
                                FROM income c JOIN income_items i ON c.id = i.income_id
                                WHERE C.date_of_issue BETWEEN INITIALDATE AND ENDDATE
                                AND c.establishment_id = ESTABLISMENT_ID
                            UNION ALL
                            SELECT c.date_of_issue as fecha, c.currency_type_id as moneda, i.total as cantidad, 1 as precio_compra, c.exchange_rate_sale as tipo_cambio, 'EGRESOS' as transaccion
                                FROM expenses c JOIN expense_items i ON c.id = i.expense_id
                                WHERE C.date_of_issue BETWEEN INITIALDATE AND ENDDATE
                                AND c.establishment_id = ESTABLISMENT_ID  
                            UNION ALL 
                             SELECT ENDDATE AS fecha, i.currency_type_id as moneda, m.stock as cantidad, i.sale_unit_price as precio_compra, 
                                 (SELECT sale_original from exchange_rates tc where tc.date = CAST(M.updated_at AS DATE)) as tipo_cambio, 'VENTAS' as transaccion
                                FROM item_warehouse m JOIN items i ON m.item_id = i.id 
                                WHERE M.updated_at BETWEEN INITIALDATE AND ENDDATE
                                AND i.warehouse_id = WAREHOUSE_ID
                            UNION ALL
                            SELECT DISTINCT ENDDATE AS fecha, i.currency_type_id as moneda,  w.stock as cantidad, i.purchase_unit_price as precio_compra, 
                                 (SELECT sale_original from exchange_rates tc where tc.date = CAST(M.date_of_movement AS DATE)) as tipo_cambio, 'SFMC' as transaccion
                                FROM item_movement m JOIN items i ON m.item_id = i.id
                                     JOIN item_warehouse w ON w.id = m.item_id 
                                WHERE m.date_of_movement BETWEEN INITIALDATE AND ENDDATE
                                AND i.warehouse_id = WAREHOUSE_ID;
                          
                                               



                        DECLARE CONTINUE HANDLER FOR NOT FOUND SET VAR_FINAL = 1;


                        OPEN cursorFinalBalance;
                        DELETE FROM PROFITS;
                        
                        bucle: LOOP

                            FETCH cursorFinalBalance INTO DATE_ISSUE, CURRENCY_TYPE, QTY, PRICE, EXCHANGE_RATE_PER_DAY, TRANS;

                            IF VAR_FINAL = 1 THEN
                            LEAVE bucle;
                            END IF;
                            /*VERIFICAR SI EXISTE REGISTRO EN TABLA PROFITS*/
                            SELECT COUNT(*) INTO RESULT FROM PROFITS;
                            /*VERIFICAR TRANSACCION*/
                            IF CURRENCY_TYPE = 'DOP' THEN
                                IF RESULT = 0 OR IFNULL(RESULT,0) = 0 THEN
                                        IF TRANS = 'SFMA' THEN
                                        SET MONTOSFMA = MONTOSFMA + (QTY * PRICE);
                                        INSERT INTO PROFITS(FBPM, PURCHASES, EXPENSES, INCOME, SALES, FBAM)
                                        VALUES(MONTOSFMA,0,0,0,0,0);
                                        ELSE
                                        IF TRANS = 'COMPRAS' THEN
                                            SET MONTOSCOMPRAS = MONTOSCOMPRAS + (QTY * PRICE);
                                            INSERT INTO PROFITS(FBPM, PURCHASES, EXPENSES, INCOME, SALES, FBAM)
                                            VALUES(0,MONTOSCOMPRAS,0,0,0,0);
                                        ELSE
                                            IF TRANS = 'EGRESOS' THEN
                                                SET MONTOSEGRESOS = MONTOSEGRESOS + (QTY * PRICE);
                                                INSERT INTO PROFITS(FBPM, PURCHASES, EXPENSES, INCOME, SALES, FBAM)
                                                VALUES(0,0,MONTOSEGRESOS,0,0,0);
                                            ELSE
                                                IF TRANS = 'INGRESOS' THEN
                                                    SET MONTOSINGRESOS = MONTOSINGRESOS + (QTY * PRICE);
                                                    INSERT INTO PROFITS(FBPM, PURCHASES, EXPENSES, INCOME, SALES, FBAM)
                                                    VALUES(0,0,0,MONTOSINGRESOS,0,0);
                                                ELSE
                                                    IF TRANS = 'VENTAS' THEN
                                                    SET MONTOSVENTAS = MONTOSVENTAS + (QTY * PRICE);
                                                    INSERT INTO PROFITS(FBPM, PURCHASES, EXPENSES, INCOME, SALES, FBAM)
                                                    VALUES(0,0,0,0,MONTOSVENTAS,0);
                                                    ELSE
                                                    SET MONTOSSFMV = MONTOSSFMV + (QTY * PRICE);
                                                    INSERT INTO PROFITS(FBPM, PURCHASES, EXPENSES, INCOME, SALES, FBAM)
                                                    VALUES(0,0,0,0,0,MONTOSSFMV);
                                                    END IF; 
                                                END IF;
                                            END IF; 
                                            END IF; 
                                        END IF; 
                                    ELSE
                                        IF TRANS = 'SFMA' THEN
                                        SET MONTOSFMA = MONTOSFMA + (QTY * PRICE);
                                        UPDATE PROFITS SET FBPM = MONTOSFMA;
                                        ELSE
                                        IF TRANS = 'COMPRAS' THEN
                                            SET MONTOSCOMPRAS = MONTOSCOMPRAS + (QTY * PRICE);
                                            UPDATE PROFITS SET PURCHASES = MONTOSCOMPRAS;
                                        ELSE
                                            IF TRANS = 'INGRESOS' THEN
                                                SET MONTOSINGRESOS = MONTOSINGRESOS + (QTY * PRICE);
                                                UPDATE PROFITS SET INCOME = MONTOSINGRESOS;
                                            ELSE
                                                IF TRANS = 'EGRESOS' THEN
                                                    SET MONTOSEGRESOS = MONTOSEGRESOS + (QTY * PRICE);
                                                    UPDATE PROFITS SET EXPENSES = MONTOSEGRESOS;
                                                ELSE
                                                    IF TRANS = 'VENTAS' THEN
                                                    SET MONTOSVENTAS = MONTOSVENTAS + (QTY * PRICE);
                                                    UPDATE PROFITS SET SALES = MONTOSVENTAS;
                                                    ELSE
                                                        SET MONTOSSFMV = MONTOSSFMV + (QTY * PRICE);
                                                    UPDATE PROFITS SET FBAM =  MONTOSSFMV;
                                                    END IF; 
                                                END IF; 
                                            END IF; 
                                        END IF; 
                                        END IF; 
                                    END IF;
                            ELSE
                                IF RESULT = 0 OR IFNULL(RESULT,0) = 0 THEN
                                        IF TRANS = 'SFMA' THEN
                                        SET MONTOSFMA = MONTOSFMA + (QTY * PRICE * EXCHANGE_RATE_PER_DAY);
                                        INSERT INTO PROFITS(FBPM, PURCHASES, EXPENSES, INCOME, SALES, FBAM)
                                        VALUES(MONTOSFMA,0,0,0,0,0);
                                        ELSE
                                        IF TRANS = 'COMPRAS' THEN
                                            SET MONTOSCOMPRAS = MONTOSCOMPRAS + (QTY * PRICE * EXCHANGE_RATE_PER_DAY);
                                            INSERT INTO PROFITS(FBPM, PURCHASES, EXPENSES, INCOME, SALES, FBAM)
                                            VALUES(0,MONTOSCOMPRAS,0,0,0,0);
                                        ELSE
                                            IF TRANS = 'EGRESOS' THEN
                                                SET MONTOSEGRESOS = MONTOSEGRESOS + (QTY * PRICE * EXCHANGE_RATE_PER_DAY);
                                                INSERT INTO PROFITS(FBPM, PURCHASES, EXPENSES, INCOME, SALES, FBAM)
                                                VALUES(0,0,MONTOSEGRESOS,0,0,0);
                                            ELSE
                                                IF TRANS = 'INGRESOS' THEN
                                                    SET MONTOSINGRESOS = MONTOSINGRESOS + (QTY * PRICE * EXCHANGE_RATE_PER_DAY);
                                                    INSERT INTO PROFITS(FBPM, PURCHASES, EXPENSES, INCOME, SALES, FBAM)
                                                    VALUES(0,0,0,MONTOSINGRESOS,0,0);
                                                ELSE
                                                    IF TRANS = 'VENTAS' THEN
                                                    SET MONTOSVENTAS = MONTOSVENTAS + (QTY * PRICE * EXCHANGE_RATE_PER_DAY);
                                                    INSERT INTO PROFITS(FBPM, PURCHASES, EXPENSES, INCOME, SALES, FBAM)
                                                    VALUES(0,0,0,0,MONTOSVENTAS,0);
                                                    ELSE
                                                    SET MONTOSSFMV = MONTOSSFMV + (QTY * PRICE * EXCHANGE_RATE_PER_DAY);
                                                    INSERT INTO PROFITS(FBPM, PURCHASES, EXPENSES, INCOME, SALES, FBAM)
                                                    VALUES(0,0,0,0,0,MONTOSSFMV);
                                                    END IF; 
                                                END IF;
                                            END IF; 
                                            END IF; 
                                        END IF; 
                                    ELSE
                                        IF TRANS = 'SFMA' THEN
                                        SET MONTOSFMA = MONTOSFMA + (QTY * PRICE * EXCHANGE_RATE_PER_DAY);
                                        UPDATE PROFITS SET FBPM = MONTOSFMA;
                                        ELSE
                                        IF TRANS = 'COMPRAS' THEN
                                            SET MONTOSCOMPRAS = MONTOSCOMPRAS + (QTY * PRICE * EXCHANGE_RATE_PER_DAY);
                                            UPDATE PROFITS SET PURCHASES = MONTOSCOMPRAS;
                                        ELSE
                                            IF TRANS = 'INGRESOS' THEN
                                                SET MONTOSINGRESOS = MONTOSINGRESOS + (QTY * PRICE * EXCHANGE_RATE_PER_DAY);
                                                UPDATE PROFITS SET INCOME = MONTOSINGRESOS;
                                            ELSE
                                                IF TRANS = 'EGRESOS' THEN
                                                    SET MONTOSEGRESOS = MONTOSEGRESOS + (QTY * PRICE * EXCHANGE_RATE_PER_DAY);
                                                    UPDATE PROFITS SET EXPENSES = MONTOSEGRESOS;
                                                ELSE
                                                    IF TRANS = 'VENTAS' THEN
                                                    SET MONTOSVENTAS = MONTOSVENTAS + (QTY * PRICE * EXCHANGE_RATE_PER_DAY);
                                                    UPDATE PROFITS SET SALES = MONTOSVENTAS;
                                                    ELSE
                                                        SET MONTOSSFMV = MONTOSSFMV + (QTY * PRICE * EXCHANGE_RATE_PER_DAY);
                                                    UPDATE PROFITS SET FBAM =  MONTOSSFMV;
                                                    END IF; 
                                                END IF; 
                                            END IF; 
                                        END IF; 
                                        END IF; 
                                    END IF;
                            END IF;
                            
                            

                        END LOOP bucle;
                        CLOSE cursorFinalBalance;

                        END";  

                        DB::unprepared($sql);
        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS spGetProfit;');
    }
   
}