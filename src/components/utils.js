export const convertToSlug = (text) => {
  //OLD METHOD
  // return text
  //   .toLowerCase()
  //   .replace(/ /g,'-')
  //   .replace(/[^\w-]+/g,'');

  if (text) {
    return text
      .toLowerCase()
      .replace(/[^\w ]+/g, "")
      .replace(/ +/g, "-");
  } else {
    return "";
  }
};