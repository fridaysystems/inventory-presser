(()=>{"use strict";const e=window.wp.blocks,n=window.wp.i18n,o=window.wp.components,t=window.wp.coreData,i=window.wp.data,r=window.wp.blockEditor,p=window.ReactJSXRuntime,s=JSON.parse('{"UU":"inventory-presser/down-payment"}');(0,e.getBlockType)(s.UU)||(0,e.registerBlockType)(s.UU,{edit:function({isSelected:e}){const s=(0,i.useSelect)((e=>e("core/editor").getCurrentPostType()),[]),[w,c]=(0,t.useEntityProp)("postType",s,"meta"),d=(0,r.useBlockProps)();return e?(0,p.jsx)("div",{...d,children:(0,p.jsx)(o.TextControl,{label:(0,n.__)("Down Payment","inventory-presser"),value:w[invp_blocks.meta_prefix+"down_payment"],onChange:e=>c({...w,[invp_blocks.meta_prefix+"down_payment"]:e})})}):(0,p.jsxs)("div",{...d,children:[" ",invpFormatCurrency(w[invp_blocks.meta_prefix+"down_payment"])," "]})}})})();