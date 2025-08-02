<?php

namespace App\DataFixtures;

use App\Entity\Configuration;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class GarageCustomFieldsFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Customer Custom Fields
        $customerTypeField = new Configuration();
        $customerTypeField->setName('customer.meta-field.customer_type');
        $customerTypeField->setValue('{"name":"customer_type","label":"Loại khách","type":"choice","constraints":[],"required":false,"options":{"khachcu":"Khách cũ","khachmoi":"Khách mới"}}');
        $manager->persist($customerTypeField);

        // Project Custom Fields
        
        // Nhóm 1: Thông tin Xe
        $licensePlateField = new Configuration();
        $licensePlateField->setName('project.meta-field.license_plate');
        $licensePlateField->setValue('{"name":"license_plate","label":"Biển số xe","type":"text","constraints":[],"required":true,"options":[]}');
        $manager->persist($licensePlateField);
        
        $vehicleBrandField = new Configuration();
        $vehicleBrandField->setName('project.meta-field.vehicle_brand');
        $vehicleBrandField->setValue('{"name":"vehicle_brand","label":"Hãng xe","type":"text","constraints":[],"required":false,"options":[]}');
        $manager->persist($vehicleBrandField);
        
        $vehicleModelField = new Configuration();
        $vehicleModelField->setName('project.meta-field.vehicle_model');
        $vehicleModelField->setValue('{"name":"vehicle_model","label":"Tên xe (Model)","type":"text","constraints":[],"required":false,"options":[]}');
        $manager->persist($vehicleModelField);
        
        // Nhóm 2: Thông tin Đơn hàng
        $orderCodeField = new Configuration();
        $orderCodeField->setName('project.meta-field.order_code');
        $orderCodeField->setValue('{"name":"order_code","label":"Mã đơn","type":"text","constraints":[],"required":false,"options":[]}');
        $manager->persist($orderCodeField);
        
        $quoteApprovalDateField = new Configuration();
        $quoteApprovalDateField->setName('project.meta-field.quote_approval_date');
        $quoteApprovalDateField->setValue('{"name":"quote_approval_date","label":"Ngày duyệt giá","type":"date","constraints":[],"required":false,"options":[]}');
        $manager->persist($quoteApprovalDateField);
        
        $estimatedHandoverDateField = new Configuration();
        $estimatedHandoverDateField->setName('project.meta-field.estimated_handover_date');
        $estimatedHandoverDateField->setValue('{"name":"estimated_handover_date","label":"Ngày dự kiến giao xe","type":"date","constraints":[],"required":false,"options":[]}');
        $manager->persist($estimatedHandoverDateField);
        
        // Nhóm 3: Phân loại Dịch vụ & Doanh thu
        $serviceTypeField = new Configuration();
        $serviceTypeField->setName('project.meta-field.service_type');
        $serviceTypeField->setValue('{"name":"service_type","label":"Loại dịch vụ","type":"choice","constraints":[],"required":false,"multiple":true,"options":{"QM":"Bảo dưỡng nhanh","PM":"Bảo dưỡng định kỳ","EN":"Hệ thống động cơ","BR":"Hệ thống phanh","TR":"Hệ thống truyền động","EL":"Hệ thống điện","AC":"Hệ thống điều hòa","SU":"Hệ thống treo","ST":"Hệ thống lái","FU":"Hệ thống nhiên liệu","EX":"Hệ thống xả","TY":"Hệ thống lốp","BD":"Dịch vụ đồng","PT":"Dịch vụ sơn","CC":"Dịch vụ chăm sóc xe","DT":"Dịch vụ detailing","CL":"Dịch vụ thu hộ","OS":"Dịch vụ thuê ngoài"}}');
        $manager->persist($serviceTypeField);
        
        // Nhóm 4: Nhân sự & Nguồn
        $customerSourceField = new Configuration();
        $customerSourceField->setName('project.meta-field.customer_source');
        $customerSourceField->setValue('{"name":"customer_source","label":"Nguồn khách hàng","type":"choice","constraints":[],"required":false,"options":{"truc_tiep":"Tới trực tiếp","zalo":"Zalo","facebook":"Facebook","hotline":"Hotline"}}');
        $manager->persist($customerSourceField);
        
        $serviceAdvisorField = new Configuration();
        $serviceAdvisorField->setName('project.meta-field.service_advisor');
        $serviceAdvisorField->setValue('{"name":"service_advisor","label":"Cố vấn dịch vụ","type":"user","constraints":[],"required":false,"options":[]}');
        $manager->persist($serviceAdvisorField);
        
        $salesStaffField = new Configuration();
        $salesStaffField->setName('project.meta-field.sales_staff');
        $salesStaffField->setValue('{"name":"sales_staff","label":"Nhân viên Kinh doanh","type":"user","constraints":[],"required":false,"options":[]}');
        $manager->persist($salesStaffField);
        
        // Nhóm 5: Trạng thái
        $quoteStatusField = new Configuration();
        $quoteStatusField->setName('project.meta-field.quote_status');
        $quoteStatusField->setValue('{"name":"quote_status","label":"Trạng thái báo giá","type":"choice","constraints":[],"required":false,"options":{"draft":"Draft","done":"Done","sale":"Sale"}}');
        $manager->persist($quoteStatusField);
        
        $invoiceStatusField = new Configuration();
        $invoiceStatusField->setName('project.meta-field.invoice_status');
        $invoiceStatusField->setValue('{"name":"invoice_status","label":"Trạng thái hóa đơn","type":"choice","constraints":[],"required":false,"options":{"no":"No","invoiced":"Invoiced"}}');
        $manager->persist($invoiceStatusField);

        $manager->flush();
    }
}
