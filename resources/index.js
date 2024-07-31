import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { getSetting } from '@woocommerce/settings';
import { registerPaymentMethod } from '@woocommerce/blocks-registry';

const settings = getSetting('wc_sellgate_gateway_data', {});

const defaultLabel = __(
  'Sellgate',
  'sellgate'
);

const label = decodeEntities(settings.title) || defaultLabel;

const Content = (props) => {
  return decodeEntities(settings.description);
};

const Label = (props) => {
  const { PaymentMethodLabel } = props.components;
  return <PaymentMethodLabel text={label} />;
};

const Sellgate = {
  name: 'wc_sellgate_gateway',
  label: (
    <div>
      &nbsp;
      {label}
    </div>
  ),
  content: <Content />,
  edit: <Content />,
  canMakePayment: () => true,
  ariaLabel: label,
  supports: {
    features: settings.supports,
  },
};

registerPaymentMethod(Sellgate);
