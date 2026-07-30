<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddRecordsMenu extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("
            SET IDENTITY_INSERT [dbo].[menus] ON
            IF NOT EXISTS(SELECT TOP(1) * FROM menus WHERE menu = 'Document Type Setup')
                BEGIN
                    INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (118, N'Document Type Setup', N'Document Type Setup', 1, NULL, NULL, N'document_type_setup', 4, 0);
                END

            IF NOT EXISTS(SELECT TOP(1) * FROM menus WHERE menu = 'Step Increment Approval')
                BEGIN
                    INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (119, N'Step Increment Approval', N'Step Increment Approval', 1, NULL, NULL, N'step_increment_approval', 1, 0)
                END
            SET IDENTITY_INSERT [dbo].[menus] OFF

            SET IDENTITY_INSERT [dbo].[document_numbers] ON
            IF NOT EXISTS(SELECT TOP(1) * FROM document_numbers WHERE [name] = 'Certificate of Last Day of Service')
                BEGIN
                    INSERT INTO [dbo].[document_numbers] ([name], [id], [rd_document_number], [rd_revision], [co_document_number], [co_revision], [key], [created_at], [updated_at]) VALUES (N'Certificate of Last Day of Service', 10, N'A-HRDD.FRM.2015.015', N'Revision 1 / 07-26-2022', N'A-HRDD.FRM.2015.015', N'Revision 1 / 07-26-2022', NULL, NULL, NULL)
                END

            IF NOT EXISTS(SELECT TOP(1) * FROM document_numbers WHERE [name] = 'OJT Certificate')
                BEGIN
                    INSERT INTO [dbo].[document_numbers] ([name], [id], [rd_document_number], [rd_revision], [co_document_number], [co_revision], [key], [created_at], [updated_at]) VALUES (N'OJT Certificate', 11, N'A-HRDD.FRM.2015.015', N'Revision 1 / 07-26-2022', N'A-HRDD.FRM.2015.015', N'Revision 1 / 07-26-2022', NULL, NULL, NULL)
                END
            SET IDENTITY_INSERT [dbo].[document_numbers] OFF
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
