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

export const fetchToken = async (token) => {
  return fetch("https://tools.aeropage.io/api/token/" + token, { redirect: "follow" })
      .then((responseAP) => responseAP.json())
}