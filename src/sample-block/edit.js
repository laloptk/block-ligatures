import { useBlockProps } from '@wordpress/block-editor';

import './editor.scss';

export default function Edit() {
	return <p { ...useBlockProps() }>{ 'Block Ligatures — sample block.' }</p>;
}
