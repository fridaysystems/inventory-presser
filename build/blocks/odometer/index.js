(()=>{"use strict";const e=window.wp.blocks,o=window.wp.i18n,t=window.wp.components,n=window.wp.coreData,i=window.wp.blockEditor,r=window.ReactJSXRuntime,s=JSON.parse('{"UU":"inventory-presser/odometer"}');(0,e.getBlockType)(s.UU)||(0,e.registerBlockType)(s.UU,{edit:function({isSelected:e,context:s}){const{postType:p}=s,[c,d]=(0,n.useEntityProp)("postType",p,"meta"),l=(0,i.useBlockProps)();return e?(0,r.jsx)("div",{...l,children:(0,r.jsx)(t.TextControl,{label:(0,o.__)("Odometer","inventory-presser"),value:c[invp_blocks.meta_prefix+"odometer"],onChange:e=>d({...c,[invp_blocks.meta_prefix+"odometer"]:e})})}):(0,r.jsxs)("div",{...l,children:[" ",Number(c[invp_blocks.meta_prefix+"odometer"]).toLocaleString()+" "+invp_blocks.odometer_units," "]})}})})();