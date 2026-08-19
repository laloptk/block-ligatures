import { useBlockProps } from '@wordpress/block-editor';

export default function save() {
	return <p { ...useBlockProps.save() }>{ 'Block Ligatures — sample block.' }</p>;
}
