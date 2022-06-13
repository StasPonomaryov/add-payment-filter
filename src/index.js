import { addFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';

const addPaymentMethodFilters = (filters) => {
  console.log('>>>CALLED ADD PAYMENT METHOD FILTERS');
  return [{
      label: __('Payment methods', 'plugin-domain'),
      staticParams: [],
      param: '_shop_order_payment_method',
      showFilters: () => true,
      defaultValue: 'All',
      filters: [...(wcSettings.multiPaymentMethods || [])],
  }, ...filters, ];
};
addFilter('woocommerce_admin_orders_report_filters', 'plugin-domain', addPaymentMethodFilters);

const addTableColumn = (reportTableData) => {
  console.log('>>>CALLED ADD TABLE COLUMN');
  const includedReports = ['orders'];
  if (!includedReports.includes(reportTableData.endpoint)) {
      return reportTableData;
  }
  const newHeaders = [{
      label: 'Payment Method',
      key: 'payment',
  }, ...reportTableData.headers, ];
  const newRows = reportTableData.rows.map((row, index) => {
      const item = reportTableData.items.data[index];
      const PaymentMethod = item.payment;
      const newRow = [{
          display: PaymentMethod,
          value: PaymentMethod,
      }, ...row, ];
      return newRow;
  });
  reportTableData.headers = newHeaders;
  reportTableData.rows = newRows;
  return reportTableData;
};
addFilter('woocommerce_admin_report_table', 'plugin-domain', addTableColumn);

const persistQueries = (params) => {
  console.log('>>>CALLED PERSIST QUERIES');
  params.push('payment');
  return params;
};
addFilter('woocommerce_admin_persisted_queries', 'plugin-domain', persistQueries);

const updateReportCurrencies = (config) => {
  return config;
};
addFilter('woocommerce_admin_report_payment', 'plugin-domain', updateReportCurrencies);